import http from 'k6/http';
import { sleep } from 'k6';

export default function () {
    http.get('http://nginx.default.svc.cluster.local/hello');
    sleep(1);
}