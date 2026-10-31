<?php

class SRVJob_Scripts {
    public static function __start_job(array $params = []) :array {
        SRVJob::setLogfile("greenQL_script_runner.log");

        $script = $params["script_name"] ?? null;
        $scriptParams = $params["script_params"] ?? [];
        $ctx = $params["script_ctx"] ?? [];

        if (!is_string($script) || trim($script) === "" || !is_array($scriptParams) || !is_array($ctx)) {
            return SRVJob::returnError("script_name (string), script_params (array) und script_ctx (array) erwartet.");
        }

        $res = GBDB::runScript($script, $scriptParams, $ctx);

        SRVJob::log(json_encode($res, JSON_PRETTY_PRINT));

        return $res;
    }
}
