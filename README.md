# wechat_user
微信公共平台接口操作  
#使用方法

use Zzx\user\wechat\WxCommon;  
可进行 new WxCommon();实例化操作，也可以进行 extends Wxcommon 继承  
初始化时，配置好appid和appsercret  

# 使用GET方法获取时
获取access_token接口  
$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";  
$return = $this->requestAndCheck($url, 'GET');  

#使用post方法时
模板发送接口  
$url ="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";  
$return = $this->requestAndCheck($url, 'POST', $post);  

温馨提示
按照 https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277 上的接口规范
根据需要处理相关逻辑


