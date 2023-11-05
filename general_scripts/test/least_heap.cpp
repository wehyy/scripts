#include<iostream>
#include<vector>
#include<queue>
#include<sstream>

using namespace std;
string get_queue_result(priority_queue<int> &pq, int n) {
    stringstream ss;
    queue<int> temp;
    for (int i = 0; i < n; i++) {
        if (pq.empty()) break;
        temp.push(pq.top());
        ss << pq.top();
        pq.pop();
    }
    while (!temp.empty()) {
        pq.push(temp.front());
        temp.pop();
    }
    return ss.str();
};

int main() {
    int n, m;
    cin >> n >> m;
    priority_queue<int> pq;
    for (int i = 0; i < m; i++) {
        int a, b;
        cin >> a >> b;
        for (int j = 0; j < a; j++) {
            pq.push(b);
        }
        string result = get_queue_result(pq, a);
        cout << result << endl;
    }
    return 0;
}