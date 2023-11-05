<?php
//benchmark.php
function testTp($con, $qps, $type) {

    if ($type == "default") {
        $strCmd = "kubectl exec wrk-74fcc4bdf9-j2zsh -- wrk -t1 -c%d -d30s -R%d --latency http://nginx:80";
        $topSet = array('wrk', 'wrk-74fcc4bdf9-j2zsh -c istio-proxy', 'nginx-b468df7fd-wgqtx -c istio-proxy', 'nginx-b468df7fd-wgqtx -c nginx');
    }
    elseif ($type == "cryptomb") {
        $strCmd = "kubectl exec wrk-74fcc4bdf9-j2zsh -- wrk -t1 -c%d -d30s -R%d --latency http://nginx-cryptomb:80";
        $topSet = array('wrk', 'wrk-74fcc4bdf9-j2zsh -c istio-proxy', 'nginx-cryptomb-5b5d6b8d54-glxvf -c istio-proxy', 'nginx-cryptomb-5b5d6b8d54-glxvf -c nginx');
    }

    $wrkFile = "./wrk-result/{$type}/wrk/latency_t1_c{$con}_R{$qps}_30s";
    $logPath = "./wrk-result/{$type}/top/";
    $wrkCmd = sprintf($strCmd, $con, $qps);
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
                if(!is_dir($logPath)) {
                    mkdir($logPath);
                }
                if ($index == 1) {
                    $topCmd = "kubectl exec {$topSet[$index]} -- top -b -n 60 -d 0.5 | grep envoy > {$logPath}/wrk-envoy-top_C{$con}_R{$qps}.log";
                }
                elseif ($index == 2) {
                    $topCmd = "kubectl exec {$topSet[$index]} -- top -b -n 60 -d 0.5 | grep envoy > {$logPath}/nginx-envoy-top_C{$con}_R{$qps}.log";
                }
                elseif ($index == 3) {
                    $topCmd = "kubectl exec {$topSet[$index]} -- top -b -n 60 -d 0.5 | grep nginx > {$logPath}/nginx-top_C{$con}_R{$qps}.log";
                }
                exec($topCmd);
                exit;
            }
        }
    }
}

$cRps = array(200, 400, 800, 1000, 2000, 4000, 8000, 10000,
              12000);

$cCon = array(1, 10, 100, 200);

$cType = array('default', 'cryptomb');

foreach ($cCon as $c) {
    foreach ($cRps as $r) {
        foreach ($cType as $t) {
            echo "--------------------------------\n";
            echo "--start benchmark: con: {$c} rps: {$r} type: {$t}--\n";
            echo "--------------------------------\n";
            testTp($c, $r, $t);
            sleep(40);
            echo "--------------------------------\n";
            echo "--end benchmark: con: {$c} rps: {$r} type: {$t}--\n";
            echo "--------------------------------\n";
        }
    }
}

echo "done\n";
?>