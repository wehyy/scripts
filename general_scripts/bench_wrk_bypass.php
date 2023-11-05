<?php
//benchmark.php
function testTp($con, $qps) {
    $wrkFile = './sidecar-raw-result/wrk/latency_t1_c'.$con.'_R'.$qps.'_50s';
    $strCmd = "kubectl exec wrk-85d54c6cd9-mtgbn -- wrk -t1 -c%d -d50s -R%d --latency http://nginx-service:80";
    $wrkCmd = sprintf($strCmd, $con, $qps);

    $podSet = array('wrk', ' wrk-85d54c6cd9-mtgbn -c istio-proxy', 'nginx-6495dfbdbd-gkp7l -c istio-proxy', 'nginx-6495dfbdbd-gkp7l -c nginx');

    $arrAll = array();

    echo $wrkFile, ": start to exec ",$wrkCmd,"\n";

    foreach (range(0, 3) as $index) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo "failed to fork!\n";
            exit;
        } elseif ($pid) { // parent process: do nothing
            $pid = posix_getpid();
            echo "there is the parent, pid: $pid\n";
        } else {  // child process
            if ($index == 0) { // start the wrk test process
                $rawCmd = "{$wrkCmd} > {$wrkFile}";
                exec($rawCmd);
                exit;
            } else { // start the top log process
                if ($index == 1) {
                    $logPath = "./sidecar-raw-result/top/wrk-envoy";
                    $topCmd = "kubectl exec {$podSet[$index]} -- top -b -n 120 -d 0.5 | grep envoy > {$logPath}/top_R{$qps}.log";
                }
                elseif ($index == 2) {
                    $logPath = "./sidecar-raw-result/top/nginx-envoy";
                    $topCmd = "kubectl exec {$podSet[$index]} -- top -b -n 120 -d 0.5 | grep envoy > {$logPath}/top_R{$qps}.log";
                }
                elseif ($index == 3) {
                    $logPath = "./sidecar-raw-result/top/nginx-nginx";
                    $topCmd = "kubectl exec {$podSet[$index]} -- top -b -n 120 -d 0.5 | grep nginx > {$logPath}/top_R{$qps}.log";
                }
                if(!is_dir($logPath)) {
                    mkdir($logPath);
                }
                exec($topCmd);
                exit;
            }
        }
    }
}

$cRps = array(1000, 2000, 4000, 8000, 10000,
              12000, 14000, 16000, 18000, 20000);

$cCon = array(1, 4, 10, 50, 100, 2x00);

foreach ($cCon as $c) {
    foreach ($cRps as $r) {
        echo "--------------------------------\n";
        echo "--start benchmark: con: ",$c," rps: ",$r,"--\n";
        echo "--------------------------------\n";
        testTp($c, $r);
        echo "------------sleep(100s)-----------\n";
        sleep(100);
        echo "--------------------------------\n";
        echo "--end benchmark: con: ",$c," rps: ",$r,"--\n";
        echo "--------------------------------\n";
    }
}

echo "done\n";
?>