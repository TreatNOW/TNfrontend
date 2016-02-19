<?php

/**
 * Static class for application level data
 */
class Application {

	static function getVersion() {
		$fh = fopen("../VERSION", "r", true); // in root directory
		if (!$fh) {
			return;
		}
		$line = fgets($fh);
		fclose($fh);
		return trim($line);
	}

	//version counter
	const VERSION = '1.0.0';
	
    //config constants
    const CONFIG_DOMAIN        = 'site.domain';
    const CONFIG_PARTNERCODE   = 'site.partnerCode';
    const CONFIG_ENABLEMOBILE  = 'site.enableMobileApp';
    const CONFIG_ACCOUNTID     = 'site.accountId';
    const CONFIG_ZIDMIURI      = 'zidmi.uri';
    const CONFIG_ZIDMI_CDN_URI = 'zidmi.cdn-uri';

    //cookies
    const USER_COOKIE = 'user_key';
    const PARTNER_CODE = 'TREAT';
    const SITE_CODE = 'TREAT';

    static function GetAccountId() {
        $accountId = SessionManager::GetAccountId();
        if ($accountId == -1) {
            $accountId = Config::Get(Application::CONFIG_ACCOUNTID);
        }
        return $accountId;
    }

    static function GetFullUri($uri) {
        return 'http://'.Config::Get(Application::CONFIG_DOMAIN).$uri;
    }

    static function GetUser() {
        $user = null;
        //if cookie set then load
        if (array_key_exists(self::USER_COOKIE, $_COOKIE)) {
            $user = User::FromKey($_COOKIE[self::USER_COOKIE], self::PARTNER_CODE);
        }
        //if null create new
        if (is_null($user)) {
            $user = new User();
            $user->setPartnerCode(self::PARTNER_CODE);
            $user->setUserKey(md5($_SERVER['REMOTE_ADDR'].$_SERVER['REQUEST_URI'].$_SERVER['REQUEST_TIME']));
            if (array_key_exists('HTTP_HOST', $_SERVER)) { $user->setLandingDomain($_SERVER['HTTP_HOST']); }
            if (array_key_exists('REQUEST_URI', $_SERVER)) { $user->setLandingUri($_SERVER['REQUEST_URI']); }
            if (array_key_exists('HTTP_REFERER', $_SERVER)) { $user->setRefererUri($_SERVER['HTTP_REFERER']); }
            if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) { $user->setUserAgent($_SERVER['HTTP_USER_AGENT']); }
            if (array_key_exists('REMOTE_ADDR', $_SERVER)) { $user->setIpAddress($_SERVER['REMOTE_ADDR']); }
            $user->save();
            setcookie(self::USER_COOKIE, $user->getUserKey(), 0, '/');
        }
        return $user;
    }

    static function GetTemplatePath($templateName) {
        $path = 'lib/site/templates/'.$templateName;
        for ($fileDepth = substr_count($_SERVER['PHP_SELF'], '/') - 1; $fileDepth > 0; $fileDepth--) {
            $path = '../'.$path;
        }
        return $path;
    }

    static function LoadTemplate($templateName, $values) {
        $content = file_get_contents(self::GetTemplatePath($templateName));
        foreach ($values as $key => $value) {
            $content = str_replace('{$'.$key.'}', $value, $content);
        }
        return $content;
    }

    static function SendEmail($senderAddress, $senderName, $replyToAddress, $recipientAddress, $subject, $content, $contentType = "text/plain") {
        //save to db
        $email = new Email();
        $email->simpleSave(Application::PARTNER_CODE, $senderAddress, $senderName,
                            $recipientAddress, null, $subject, 'en', $contentType, $content);
        //queue
        $queueMessage = json_encode(array('emailId' => $email->getId()));
        $email->addEvent(EmailEventType::QUEUED); //this has to happen before actually placing on the queue
        //because the email must be flagged as queued to be processed
        $mq = MessageQueue::GetInstance(MessageQueue::LOCAL);
        $mq->send(MessageQueue::EMAIL_QUEUE, $queueMessage);
        return $email->getId();
/*
        //set addresses
        if (!is_null($senderName)) {
            $senderAddress = $senderName.' <'.$senderAddress.'>';
        }
        //build headers
        $headers = '';
        $headers .= 'From: '.$senderAddress."\n";
        if (!is_null($replyToAddress)) {
            $headers .= 'Reply-To: '.$replyToAddress."\n";
        }
        $headers .= 'X-Mailer: PHP/' . phpversion()."\n";
        $headers .= 'Content-Type: '.$contentType.'; charset=UTF-8'."\n";
        //send
        if (mail($recipientAddress, $subject, $content, $headers)) {
            $email->addEvent(EmailEventType::SENT);
        }
        else {
            $email->addEvent(EmailEventType::FAILED);
        }
        $email->save();
  */
    }

    static function GetCdnUri($image, $width = null, $height = null) {
        return Config::Get(self::CONFIG_ZIDMI_CDN_URI).$image->getKey($width, $height);
    }


}

?>
