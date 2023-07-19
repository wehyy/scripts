<?php
//wrk benchmark, short connection and close reused-session
function testTp($con, $type) {

    $wrkClient = "wrk-7d89d6f55f-ftb7q";
    $nginxDefault = "nginx-b468df7fd-wgqtx";
    $nginxCryptomb = "nginx-cryptomb-5b5d6b8d54-glxvf";
    $wrkStr = "wrk -t4 -c{$con} -d100s";
    $topStr = "top -b -n 60 -d 0.5";
    
    if ($type == "default") {
        $wrkCmd = "kubectl exec {$wrkClient} -- {$wrkStr} -H 'Connection: Close' -H 'Host: nginx' -m 0 --latency http://nginx:80";
        $topSet = array("wrk", "{$wrkClient} -c istio-proxy", "{$nginxDefault} -c istio-proxy", "{$nginxDefault} -c nginx");
    }
    elseif ($type == "cryptomb") {
        $wrkCmd = "kubectl exec {$wrkClient} -- {$wrkStr} -H 'Connection: Close' -H 'Host: nginx-cryptomb' -m 0 --latency http://nginx-cryptomb:80";
        $topSet = array("wrk", "{$wrkClient} -c istio-proxy", "{$nginxCryptomb} -c istio-proxy", "{$nginxCryptomb} -c nginx");
    }

    $wrkPath = "./sc-result/{$type}/wrk/";
    if(!is_dir($wrkPath)) {
        mkdir($wrkPath, 0777, true);
    }
    $topPath = "./sc-result/{$type}/top/";
    if(!is_dir($topPath)) {
        mkdir($topPath, 0777, true);
    }

    $wrkFile = "{$wrkPath}/latency_t4_c{$con}_100s";
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
                    $topCmd = "kubectl exec {$topSet[$index]} -- {$topStr} | grep envoy > {$topPath}/wrk-envoy-top_C{$con}.log";
                }
                elseif ($index == 2) {
                    $topCmd = "kubectl exec {$topSet[$index]} -- {$topStr} | grep envoy > {$topPath}/nginx-envoy-top_C{$con}.log";
                }
                elseif ($index == 3) {
                    $topCmd = "kubectl exec {$topSet[$index]} -- {$topStr} | grep nginx > {$topPath}/nginx-top_C{$con}.log";
                }
                exec($topCmd);
                exit;
            }
        }
    }
}

$cCon = array(4, 16, 128, 256, 512);

$cType = array('default', 'cryptomb');

foreach ($cCon as $c) {
    foreach ($cType as $t) {
        echo "--------------------------------\n";
        echo "--start benchmark: con: {$c} type: {$t}--\n";
        echo "--------------------------------\n";
        testTp($c, $t);
        sleep(120);
        echo "--------------------------------\n";
        echo "--end benchmark: con: {$c} type: {$t}--\n";
        echo "--------------------------------\n";
    }
}

echo "done\n";
?>