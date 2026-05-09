<?php

/**
 * modules that can be run remotely via SecondServer
 *
 * how it works:
 * - all module classes must start with SRVJob_
 * - the entry function must be __start_job(array $parameters = [])
 * - __start_job() can return any value except void
 * - jobs can be started with SrvP::startJob("jobname")
 * - the job name is the class name without the SRVJob_ prefix
 * 
 * For easy development, you can use the helper functions in the class SRVJob
 */


class SRVJob_Test {
    public static function __start_job(): array {
        $name = SRVJob::getParam("name", "unknown");

        SRVJob::log("test job started for " . $name);

        return SRVJob::returnOk("test job executed", [
            "name" => $name,
            "time" => date("Y-m-d H:i:s")
        ]);
    }
}

?>