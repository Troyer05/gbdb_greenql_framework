<?php

declare(strict_types=1);

/** PHP's standard filesystem API remains available to the complete existing engine. */
final class GBDBDataStream {
    public mixed $context = null;
    private string $path = '';
    private string $mode = '';
    private string $buffer = '';
    private int $position = 0;
    private int $appendBase = 0;
    private bool $dirty = false;
    private mixed $lock = null;
    private array $directory = [];
    private int $directoryPosition = 0;

    private static function relative(string $uri) :string {
        if (!str_starts_with($uri, 'gbdbdata://store')) {
            throw new InvalidArgumentException('Ungültiger GBDB-Speicherzugriff.');
        }

        $path = ltrim(substr($uri, strlen('gbdbdata://store')), '/');
        $parts = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if ($parts === []) {
                    throw new InvalidArgumentException('Pfad verlässt den GBDB-Datenspeicher.');
                }

                array_pop($parts);
                continue;
            }

            $parts[] = $segment;
        }

        $relative = implode('/', $parts);
        GBDBRuntime::native($relative);

        return $relative;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath) :bool {
        $this->path = self::relative($path);
        $this->mode = $mode;
        $stat = GBDBRuntime::stat($this->path);
        $exists = $stat !== false;
        $type = $mode[0];

        if (($type === 'r' && !$exists) || ($type === 'x' && $exists) || ($exists && ($stat['mode'] & 0170000) === 0040000)) {
            return false;
        }

        if ($type === 'a') {
            $this->appendBase = $exists ? (int) $stat['size'] : 0;
            $this->buffer = '';
        } elseif ($type !== 'w' && $type !== 'x' && $exists) {
            $this->buffer = GBDBRuntime::read($this->path);
        }

        $this->dirty = $type === 'w' || $type === 'x' || (!$exists && $type !== 'r' && !str_ends_with($this->path, '.lock'));
        $openedPath = 'gbdbdata://store/' . $this->path;

        return true;
    }

    public function stream_read(int $count) :string {
        $payload = substr($this->buffer, $this->position, $count);
        $this->position += strlen($payload);

        return $payload;
    }

    public function stream_write(string $payload) :int {
        if ($this->mode[0] === 'r' && !str_contains($this->mode, '+')) {
            return 0;
        }

        if ($this->mode[0] === 'a') {
            $this->buffer .= $payload;
            $this->position = strlen($this->buffer);
        } else {
            if ($this->position > strlen($this->buffer)) {
                $this->buffer = str_pad($this->buffer, $this->position, "\0");
            }

            $this->buffer = substr_replace($this->buffer, $payload, $this->position, strlen($payload));
            $this->position += strlen($payload);
        }

        $this->dirty = true;

        return strlen($payload);
    }

    public function stream_flush() :bool {
        if (!$this->dirty) {
            return true;
        }

        $ok = GBDBRuntime::write($this->path, $this->buffer, $this->mode[0] === 'a');

        if ($ok) {
            $this->dirty = false;

            if ($this->mode[0] === 'a') {
                $this->appendBase += strlen($this->buffer);
                $this->buffer = '';
                $this->position = 0;
            }
        }

        return $ok;
    }

    public function stream_close() :void {
        try {
            $this->stream_flush();
        } finally {
            $this->stream_lock(LOCK_UN);
        }
    }

    public function stream_eof() :bool {
        return $this->position >= strlen($this->buffer);
    }

    public function stream_tell() :int {
        return $this->appendBase + $this->position;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET) :bool {
        $target = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => strlen($this->buffer) + $offset,
            default => -1,
        };

        if ($target < 0 || $this->mode[0] === 'a') {
            return false;
        }

        $this->position = $target;

        return true;
    }

    public function stream_truncate(int $size) :bool {
        if ($size < 0 || ($this->mode[0] === 'r' && !str_contains($this->mode, '+'))) {
            return false;
        }

        $this->buffer = str_pad(substr($this->buffer, 0, $size), $size, "\0");
        $this->dirty = true;

        return true;
    }

    public function stream_stat() :array|false {
        $stat = GBDBRuntime::stat($this->path);

        if ($stat !== false) {
            $stat[7] = $stat['size'] = $this->appendBase + strlen($this->buffer);
        }

        return $stat;
    }

    public function url_stat(string $path, int $flags) :array|false {
        return GBDBRuntime::stat(self::relative($path));
    }

    public function stream_lock(int $operation) :bool {
        if (($operation & LOCK_UN) === LOCK_UN) {
            if (is_resource($this->lock)) {
                $this->stream_flush();
                flock($this->lock, LOCK_UN);
                fclose($this->lock);
                $this->lock = null;
            }

            return true;
        }

        if (!is_resource($this->lock)) {
            $this->lock = fopen(GBDBRuntime::lockDirectory() . '/file-' . hash('sha256', $this->path) . '.lock', 'c+');
        }

        if (!is_resource($this->lock) || !flock($this->lock, $operation)) {
            return false;
        }

        // A c+/r+ reader may have waited for another writer since stream_open().
        if (!$this->dirty && $this->mode[0] !== 'a' && GBDBRuntime::stat($this->path) !== false) {
            $this->buffer = GBDBRuntime::read($this->path);
        }

        return true;
    }

    public function stream_set_option(int $option, int $argument1, int $argument2) :bool {
        return false;
    }

    public function stream_metadata(string $path, int $option, mixed $value) :bool {
        if ($option === STREAM_META_TOUCH) {
            $relative = self::relative($path);
            $payload = GBDBRuntime::stat($relative) === false ? '' : GBDBRuntime::read($relative);

            return GBDBRuntime::write($relative, $payload);
        }

        return false;
    }

    public function mkdir(string $path, int $mode, int $options) :bool {
        return GBDBRuntime::directory(self::relative($path), ($options & STREAM_MKDIR_RECURSIVE) !== 0);
    }

    public function rmdir(string $path, int $options) :bool {
        return GBDBRuntime::remove(self::relative($path), true);
    }

    public function unlink(string $path) :bool {
        return GBDBRuntime::remove(self::relative($path));
    }

    public function rename(string $from, string $to) :bool {
        return GBDBRuntime::rename(self::relative($from), self::relative($to));
    }

    public function dir_opendir(string $path, int $options) :bool {
        $relative = self::relative($path);

        if (GBDBRuntime::stat($relative) === false) {
            return false;
        }

        $this->directory = ['.', '..', ...GBDBRuntime::children($relative)];
        $this->directoryPosition = 0;

        return true;
    }

    public function dir_readdir() :string|false {
        return $this->directory[$this->directoryPosition++] ?? false;
    }

    public function dir_rewinddir() :bool {
        $this->directoryPosition = 0;

        return true;
    }

    public function dir_closedir() :bool {
        $this->directory = [];

        return true;
    }
}

/** Operations not supported natively by PHP on user-defined streams. */
final class GBDBFilesystem {
    public static function glob(string $pattern, int $flags = 0) :array|false {
        if (!str_starts_with($pattern, 'gbdbdata://')) {
            return glob($pattern, $flags);
        }

        $separator = strrpos($pattern, '/');
        $parent = substr($pattern, 0, $separator);
        $name = substr($pattern, $separator + 1);
        $parents = strpbrk($parent, '*?[') !== false ? self::glob($parent, GLOB_ONLYDIR) : [$parent];
        $result = [];

        foreach ($parents ?: [] as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            foreach (scandir($directory) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..' || (str_starts_with($entry, '.') && !str_starts_with($name, '.'))) {
                    continue;
                }

                $path = $directory . '/' . $entry;

                if (fnmatch($name, $entry) && (($flags & GLOB_ONLYDIR) === 0 || is_dir($path))) {
                    $result[] = $path . (($flags & GLOB_MARK) !== 0 && is_dir($path) ? '/' : '');
                }
            }
        }

        if (($flags & GLOB_NOSORT) === 0) {
            sort($result, SORT_STRING);
        }

        return $result === [] && ($flags & GLOB_NOCHECK) !== 0 ? [$pattern] : $result;
    }

    public static function realpath(string $path) :string|false {
        return str_starts_with($path, 'gbdbdata://') ? (file_exists($path) ? rtrim($path, '/') : false) : realpath($path);
    }

    public static function sync(mixed $handle) :bool {
        $metadata = stream_get_meta_data($handle);

        return str_starts_with($metadata['uri'] ?? '', 'gbdbdata://')
            ? fflush($handle)
            : (!function_exists('fsync') || fsync($handle));
    }
}
