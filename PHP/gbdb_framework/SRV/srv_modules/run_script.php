<?php

class SRVJob_Scripts {
    public static function __start_job(array $params = []): array {
        SRVJob::setLogfile("greenQL_script_runner.log");

        $script = $params["script_name"];
        $scriptParams = $params["script_params"];
        $ctx = $params["script_ctx"];
        $res = GBDB::runScript($script, $scriptParams, $ctx);

        SRVJob::log(json_encode($res, JSON_PRETTY_PRINT));

        return $res;
    }
}