<?php

// set error reporting level
if (version_compare(phpversion(), "5.3.0", ">=") == 1)
  error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
else
  error_reporting(E_ALL & ~E_NOTICE);


// login system init and generation code
$oAdvancedLoginSystem = new AdvancedLoginSystem();
$sLoginForm = $oAdvancedLoginSystem->getLoginBox();
echo strtr(file_get_contents('main_page.html'), array('{login_form}' => $sLoginForm));

// class AdvancedLoginSystem
class AdvancedLoginSystem {

    // variables
    var $aExistedMembers; // Existed members array
    var $aQuestions; // Logic questions

    // constructor
    function AdvancedLoginSystem() {
        session_start();

        $this->aExistedMembers = array(
            'User1' => array('hash' => 'b88c654d6c68fc37f4dda1d29935235eea9a845b', 'salt' => 'testing'), // hash = sha1(md5('password') . 'testing');
            'User2' => array('hash' => 'b88c654d6c68fc37f4dda1d29935235eea9a845b', 'salt' => 'testing'),
            'User3' => array('hash' => 'b88c654d6c68fc37f4dda1d29935235eea9a845b', 'salt' => 'testing')
        );

        $this->aQuestions = array(
            1 => array('q' => 'Winter hot or cold?', 'a' => 'cold'),
            2 => array('q' => '4 - 1 = ?', 'a' => '3'),
            3 => array('q' => 'Sun is blue or yellow?', 'a' => 'yellow'),
            4 => array('q' => 'Type "god" to process', 'a' => 'god'),
            5 => array('q' => '4 + 3 = ', 'a' => '7'),
            6 => array('q' => '10 > 5 ? (yes/no)', 'a' => 'yes')
        );
    }

    // get login box function
    function getLoginBox() {
        ob_start(); // get template of Logout form
        require_once('logout_form.html');
        $sLogoutForm = ob_get_clean();

        if (isset($_GET['logout'])) { // logout processing
            if (isset($_SESSION['member_name']) && isset($_SESSION['member_pass']))
                $this->performLogout();
        }

        if ($_POST['username'] && $_POST['password'] && $_POST['captcha']) { // login processing
            if ($this->checkLogin($_POST['username'], $_POST['password'], false) && $this->aQuestions[$_SESSION['captcha']]['a'] == $_POST['captcha']) { // successful login
                unset($_SESSION['captcha']);
                $this->performLogin($_POST['username'], $_POST['password']);
                return $sLogoutForm . '<h2>Hello ' . $_SESSION['member_name'] . '!</h2>';
            } else { // wrong login
                ob_start(); // get template of Login form
                require_once('login_form.html');
                $sLoginForm = ob_get_clean();
                $sCaptcha = $this->getLogicCaptcha();
                $sLoginForm = str_replace('{captcha}', $sCaptcha, $sLoginForm);
                return $sLoginForm . '<h2>Username or Password or Captcha is incorrect</h2>';
            }
        } else { // in case if we already logged (on refresh page):
            if ($_SESSION['member_name'] && $_SESSION['member_pass']) {
                if ($this->checkLogin($_SESSION['member_name'], $_SESSION['member_pass'])) {
                    return $sLogoutForm . '<h2>Hello ' . $_SESSION['member_name'] . '!</h2>';
                }
            }

            // otherwise - draw login form
            ob_start();
            require_once('login_form.html');
            $sLoginForm = ob_get_clean();
            $sCaptcha = $this->getLogicCaptcha();
            $sLoginForm = str_replace('{captcha}', $sCaptcha, $sLoginForm);
            return $sLoginForm;
        }
    }

    // perform login
    function performLogin($sName, $sPass) {
        $this->performLogout();

        $sSalt = $this->aExistedMembers[$sName]['salt'];
        $sPass = sha1(md5($sPass) . $sSalt);

        $_SESSION['member_name'] = $sName;
        $_SESSION['member_pass'] = $sPass;
    }

    // perform logout
    function performLogout() { 
        unset($_SESSION['member_name']);
        unset($_SESSION['member_pass']);
    }

    // check login
    function checkLogin($sName, $sPass, $isHash = true) {
        if (! $isHash) {
            $sSalt = $this->aExistedMembers[$sName]['salt'];
            $sPass = sha1(md5($sPass) . $sSalt);
        }
        return ($sPass == $this->aExistedMembers[$sName]['hash']);
    }

    // get logic captcha (question)
    function getLogicCaptcha() {
        $i = array_rand($this->aQuestions);
        $_SESSION['captcha'] = $i;
        return $this->aQuestions[$i]['q'];
    }
}

?>