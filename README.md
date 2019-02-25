# wechat_user
微信公共平台接口操作  
#使用方法

use Zzx\user\wechat\WxMethod;
```
public function __construct($config = null)
    {
        parent::__construct();
        $config['appid'] = '你的appid';
        $config['appsecret'] = '你的appsecret';
        $this->WxMethod = new WxMethod($config['appid'],$config['appsecret']);
        $this->config = $config;
    }
```

# 获取access_token
```
    public function getAccessToken()
    {
        $this->WxMethod->getAccessToken();
    }
```
# 目前已整理的微信第三方类  
1. getUserList();获取微信公共号用户列表  
2. getUserInfo('openid');获取某一用户的详细信息，参数一为用户的openid。  
3. sendMsgToOne('openid','type','content');先某一用户发送消息，参数一为用户的openid，参数二为用户的消息类型，参数三为发送内容。  
4. getAllTemplateMsg();获取公共号消息模板。  
5. sendTemplateMsg();向指定用户发送模板消息，参数一为用户的openid，参数二为消息模板 id，参数三为需要跳转的url，参数四为发送的数据（array）。  
6. getSignPackage();获取微信JS-SDK的config权限配置信息。  



