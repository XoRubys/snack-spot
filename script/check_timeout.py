import urllib.request
import json
import time
from datetime import datetime
/*
 * 检查订单超时，建议设置定时任务。
 */
URL = "https://youdomain.com/api/v1/user/order/checkTimeout"
INTERVAL = 60  # 1分钟检查一次  

# ANSI 颜色代码
GREEN = "\033[32m"
RED = "\033[31m"
RESET = "\033[0m"

while True:
    try:
        with urllib.request.urlopen(URL, timeout=30) as response:
            data = response.read().decode('utf-8')
            try:
                result = json.loads(data)
                code = result.get('code')
                if code != 200:
                    raise Exception(f"返回code异常: {code}, 响应: {data}")
                print(f"{GREEN}[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] {data}{RESET}")
            except json.JSONDecodeError:
                raise Exception(f"JSON解析失败: {data}")
    except Exception as e:
        print(f"{RED}[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] 异常: {e}{RESET}")

    time.sleep(INTERVAL)
