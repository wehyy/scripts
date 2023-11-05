<?php
//benchmark.php
function testTp($rps) {
    $wrkClient = "wrk-684f4544cd-ghjgk";
    $nginxClient = "nginx-7d975b8d4b-7j9xh";
    $waypoint = "nginx-waypoint-proxy-c75868cdb-9nq8l";
    $ztunnelOut = "ztunnel-ht26w";
    $ztunnelIn = "ztunnel-sf2mc";

    $wrkCmd = "kubectl exec -it {$wrkClient} -- wrk -t1 -c100 -d40 -R{$rps} --latency http://nginx:80";
    $topStr = "top -b -n 60 -d 0.5";

    $wrkPath = "../wrk-result-2/ambient-cost-result/wrk";
    if(!is_dir($wrkPath)) {
        mkdir($wrkPath, 0777, true);
    }
    $topPath = "../wrk-result-2/ambient-cost-result/top";
    if(!is_dir($topPath)) {
        mkdir($topPath, 0777, true);
    }
    foreach (range(0, 15) as $repeat_index) {
        foreach (range(0, 4) as $thread_id) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                echo "failed to fork!\n";
                exit;
            } elseif ($pid) { // parent thread: do nothing
                $pid = posix_getpid();
                echo "there is the parent, pid: $pid\n";
            } else { // child thread
                if ($thread_id == 0) { // start the wrk test process
                    $wrkFile = "{$wrkPath}/latency_t1_c100_R{$rps}_{$repeat_index}";
                    echo $wrkFile, ": start to exec ",$wrkCmd,"\n";
                    $rawCmd = "{$wrkCmd} > {$wrkFile}";
                    exec($rawCmd);
                    exit(0);
                } else { // start the top log process
                    if ($thread_id == 1) {
                        $logPath = "{$topPath}/ztunnel-out";
                        if(!is_dir($logPath)) {
                            mkdir($logPath, 0777, true);
                        }
                        $logFile = "{$logPath}/top_R{$rps}_{$repeat_index}";
                        $topCmd = "kubectl exec -n istio-system {$ztunnelOut} -- {$topStr} | grep envoy > {$logFile}";
                    }
                    elseif ($thread_id == 2) {
                        $logPath = "{$topPath}/ztunnel-in";
                        if(!is_dir($logPath)) {
                            mkdir($logPath, 0777, true);
                        }
                        $logFile = "{$logPath}/top_R{$rps}_{$repeat_index}";
                        $topCmd = "kubectl exec -n istio-system {$ztunnelIn} -- {$topStr} | grep envoy > {$logFile}";

                    }
                    elseif ($thread_id == 3) {
                        $logPath = "{$topPath}/waypoint";
                        if(!is_dir($logPath)) {
                            mkdir($logPath, 0777, true);
                        }
                        $logFile = "{$logPath}/top_R{$rps}_{$repeat_index}";
                        $topCmd = "kubectl exec {$waypoint} -- {$topStr} | grep envoy > {$logFile}";
                    }
                    elseif ($thread_id == 4) {
                        $logPath = "{$topPath}/nginx";
                        if(!is_dir($logPath)) {
                            mkdir($logPath, 0777, true);
                        }
                        $logFile = "{$logPath}/top_R{$rps}_{$repeat_index}";
                        $topCmd = "kubectl exec {$nginxClient} -- {$topStr} | grep nginx > {$logFile}";
                    }
                    exec($topCmd);
                    exit(0);
                }
            }
        }
        sleep(50);
    }
}

$cRps = array(5000, 10000, 15000);

foreach ($cRps as $r) {
    echo "--------------------------------\n";
    echo "--start benchmark: rps: {$r} --\n";
    echo "--------------------------------\n";
    testTp($r);
    echo "--------------------------------\n";
    echo "--end benchmark: rps: {$r} --\n";
    echo "--------------------------------\n";
}

echo "done\n";
?>