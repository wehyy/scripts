<?php
    $ppid = posix_getpid();
    $pid = pcntl_fork();
    if ($pid == -1) {
        echo 'fork子进程失败!';
    } elseif ($pid > 0) {
        echo "我是父进程,我的进程id是{$ppid}.";
        echo "\r\n";
        sleep(20); // 保持20秒，确保能被ps查到
    }else{
        $cpid = posix_getpid();
        echo "我是{$ppid}的子进程,我的进程id是{$cpid}.";
        echo "\r\n";
        sleep(20); // 保持20秒，确保能被ps查到
    }
?>