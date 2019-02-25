<?php
/**
 * Created by PhpStorm.
 * User: 钊鑫
 * Date: 2019/2/25
 * Time: 13:55
 */
namespace Zzx\user\wechat;

use Zzx\user\wechat\WxCommon;

/**
 * 微信小程序第三方平台接口封装类
 */
class WxMethod extends WxCommon
{
    private $appId;
    private $appSecret;
    private $jsapi_ticket;                  //ticket签名
    private $ticket_expire_time = '';       // 签名jsapi_ticket过期时间。
    private $expire_time = '';
    private $access_token;

    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    /**
     * 微信token获取。
     */
    public function getAccessToken()
    {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        if ($this->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->requestAndCheck($url, 'GET');
            $access_token = $res['access_token'];
            if ($access_token) {
                $this->expire_time = time() + 7000;
                $this->access_token = $access_token;
            }
        }
        return $this->access_token;
    }

    /**
     * 微信用户列表获取
     * @param string $next_openid 下一次拉取的起始id的前一个id
     */
    public function getUserList($next_openid = '')
    {
        if (!$access_token = $this->getAccessToken()) {
            $this->setError('微信token获取失败！');
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token={$access_token}&next_openid={$next_openid}";
        $res = $this->requestAndCheck($url, 'GET');
        if ($res === false) {
            return false;
        }

        return $res;
    }

    /**
     * 获取粉丝详细信息
     * @param string $openid
     * @param string $access_token 如果为null，自动获取
     * @return array|bool
     */
    public function getUserInfo($openid)
    {
        if (!$access_token = $this->getAccessToken()) {
            $this->setError('微信token获取失败！');
            return false;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $return = $this->requestAndCheck($url, 'GET');
        if ($return === false) {
            return false;
        }

        /* $wxdata[]元素：
        * subscribe	用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息。
        * openid	用户的标识，对当前公众号唯一
        * nickname	用户的昵称
        * sex	用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
        * city	用户所在城市
        * country	用户所在国家
        * province	用户所在省份
        * language	用户的语言，简体中文为zh_CN
        * headimgurl	用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
        * subscribe_time	用户关注时间，为时间戳。如果用户曾多次关注，则取最后关注时间
        * unionid	只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
        * remark	公众号运营者对粉丝的备注，公众号运营者可在微信公众平台用户管理界面对粉丝添加备注
        * groupid	用户所在的分组ID（兼容旧的用户分组接口）
        * tagid_list	用户被打上的标签ID列表
        */
        $return['sex_name'] = $this->sexName($return['sex']);
        return $return;
    }

    /**
     * sex_id 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
     */
    public function sexName($sex_id)
    {
        if ($sex_id == 1) {
            return '男';
        } else if ($sex_id == 2) {
            return '女';
        }
        return '未知';
    }

    /**
     * 向一个粉丝发送消息
     * 文档：https://mp.weixin.qq.com/wiki?action=doc&id=mp1421140547#2
     * @param $type string (text,news,image,voice,video,music,mpnews,wxcard)
     */
    public function sendMsgToOne($openid, $type, $content)
    {
        if (!$access_token = $this->getAccessToken()) {
            return false;
        }

        $data = [
            'touser' => $openid,
            'msgtype' => $type,
        ];

        if ($type == 'text') {
            $data[$type]['content'] = $content; //text
        } elseif (in_array($type, ['image', 'voice', 'mpnews'])) {
            $data[$type]['media_id'] = $content; //media_id
        } elseif ($type == 'wxcard') {
            $data[$type]['card_id'] = $content; //card_id
        } elseif ($type == 'news') {
            //$content = [{
            //     "title":"Happy Day",
            //     "description":"Is Really A Happy Day",
            //     "url":"URL",
            //     "picurl":"PIC_URL"
            //}, ...]
            $data[$type]['articles'] = $content;
        } elseif ($type == 'video') {
            //$content = {
            //    "media_id":"MEDIA_ID",
            //    "thumb_media_id":"MEDIA_ID",
            //    "title":"TITLE",
            //    "description":"DESCRIPTION"
            //}
            $data[$type] = $content;
        } elseif ($type == 'music') {
            //$content = {
            //    "title":"MUSIC_TITLE",
            //    "description":"MUSIC_DESCRIPTION",
            //    "musicurl":"MUSIC_URL",
            //    "hqmusicurl":"HQ_MUSIC_URL",
            //    "thumb_media_id":"THUMB_MEDIA_ID"
            //}
            $data[$type] = $content;
        }

        $post = $this->toJson($data);
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$access_token}";
        $return = $this->requestAndCheck($url, 'POST', $post);
        if ($return === false) {
            return false;
        }

        return true;
    }

    /**
     * 获取用户所有模板消息
     * @return bool|mixed|string
     */
    public function getAllTemplateMsg()
    {
        if (!$access_token = $this->getAccessToken()) {
            return false;
        }

        $url = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token={$access_token}";
        $return = $this->requestAndCheck($url, 'GET');
        if ($return === false) {
            return false;
        }

        //返回数据格式：
        //{"template_list": [{
        //    "template_id": "iPk5sOIt5X_flOVKn5GrTFpncEYTojx6ddbt8WYoV5s",
        //    "title": "领取奖金提醒",
        //    "primary_industry": "IT科技",
        //    "deputy_industry": "互联网|电子商务",
        //    "content": "{ {result.DATA} }\n\n领奖金额:{ {withdrawMoney.DATA} }\n领奖  时间:{ {withdrawTime.DATA} }\n银行信息:{ {cardInfo.DATA} }\n到账时间:  { {arrivedTime.DATA} }\n{ {remark.DATA} }",
        //    "example": "您已提交领奖申请\n\n领奖金额：xxxx元\n领奖时间：2013-10-10 12:22:22\n银行信息：xx银行(尾号xxxx)\n到账时间：预计xxxxxxx\n\n预计将于xxxx到达您的银行卡"
        //}, ...]}
        return $return;
    }

    /**
     * 对获取用户发送模板消息
     * @param string $openid 用户openid
     * @param string $template_id 消息模板id
     * @param string $url 模板点击跳转链接
     * @param array $data 消息模板数据的数组
     * @return bool|mixed|string
     */
    public function sendTemplateMsg($openid = 'oJIU40sj7_lB35DDJPsBfz4qHNrY', $template_id = 'qRVUP2QptyM2RAG8OuFujjtaF_MdtV1cq-rvW6FcPzk', $url = '', $data = '')
    {
        if (!$access_token = $this->getAccessToken()) {
            return false;
        }

        $post = $this->toJson([
            "touser" => $openid,
            "template_id" => $template_id,
            "url" => $url, //模板跳转链接
//            "miniprogram" => [ //小程序跳转配置
//                "appid" => "xiaochengxuappid12345",
//                "pagepath" => "index?foo=bar"
//            ],
            "data" => [
                "project" => [
                    "value" => "项目！",
                    "color" => "#173177"
                ],
                "username" => [
                    "value" => "项目负责人",
                    "color" => "#173177"
                ],
                "type" => [
                    "value" => "审核方！",
                    "color" => "#173177"
                ],
                "result" => [
                    "value" => "审核结果！",
                    "color" => "#173177"
                ],
                "time" => [
                    "value" => "审核时间！",
                    "color" => "#173177"
                ],
                "content" => [
                    "value" => "审核内容！",
                    "color" => "#173177"
                ]
            ]
        ]);
        //注：url和miniprogram都是非必填字段，若都不传则模板无跳转；若都传，会优先跳转至小程序。
        //开发者可根据实际需要选择其中一种跳转方式即可。当用户的微信客户端版本不支持跳小程序时，将会跳转至url

        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $return = $this->requestAndCheck($url, 'POST', $post);
        if ($return === false) {
            return false;
        }

        return true;
    }

    /**
     * 微信分享类JS-SDK的config权限配置
     * @return bool|mixed|string|array
     */
    public function getSignPackage() {
        $jsapiTicket = $this->getJsApiTicket();//

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    /**
     * 微信JS-SDK使用权限签名算法，第一步，获取jsapi_ticket
     */
    private function getJsApiTicket() {
        if ($this->ticket_expire_time < time()) {
            if (!$access_token = $this->getAccessToken()) {
                return false;
            }
            // 如果是企业号用以下 URL 获取 ticket
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$access_token}";
            $res = $this->requestAndCheck($url, 'GET');
            $ticket = $res['ticket'];
            if ($ticket) {
                $this->ticket_expire_time = time() + 7000;
                $this->jsapi_ticket = $ticket;
            }
        }

        return $this->jsapi_ticket;
    }

    /**
     * 微信JS-SDK使用权限签名算法，第二步，获取随机字符串
     */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}