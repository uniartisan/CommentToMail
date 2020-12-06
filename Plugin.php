<?php

/**
 * 评论邮件提醒插件,可以通过网址监控运行
 *
 * @package CommentToMail
 * @author Uniartisan
 * @version 4.2.9
 * @link https://blog.uniartisan.com/archives/CommentToMail.html
 * latest dates 2020-08-10
 */
class CommentToMail_Plugin implements Typecho_Plugin_Interface
{
        /** update 信息 */
        public static $version = '4.2.9';

        /** @var string 提交路由前缀 */
        public static $action = 'comment-to-mail';

        /** @var bool 内部请求User-Agent */
        public static $ua = 'MailMessageBrid';

        /** @var string 控制菜单链接 */
        public static $panel  = 'CommentToMail/page/console.php';

        /** @var bool 是否记录日志 */
        private static $_isMailLog  = false;

        /** @var bool 请求适配器 */
        private static $_adapter    = false;

        /**
         * 激活插件方法,如果激活失败,直接抛出异常
         *
         * @access public
         * @return void
         * @throws Typecho_Plugin_Exception
         */
        public static function activate()
        {
                self::dbInstall();
                Typecho_Plugin::factory('Widget_Feedback')->finishComment = array('CommentToMail_Plugin', 'parseComment');
                Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array('CommentToMail_Plugin', 'parseComment');
                Typecho_Plugin::factory('Widget_Comments_Edit')->mark = array('CommentToMail_Plugin', 'passComment');
                Helper::addAction(self::$action, 'CommentToMail_Action');
                Helper::addRoute('commentToMailProcessQueue', '/commentToMailProcessQueue/', 'CommentToMail_Action', 'processQueue');
                Helper::addPanel(1, self::$panel, '评论邮件提醒', '评论邮件提醒控制台', 'administrator');
                return _t('请设置邮箱信息，以使插件正常使用！');
        }

        /**
         * 禁用插件方法,如果禁用失败,直接抛出异常
         *
         * @static
         * @access public
         * @return void
         * @throws Typecho_Plugin_Exception
         */
        public static function deactivate()
        {
                Helper::removeAction(self::$action);
                Helper::removeRoute('commentToMailProcessQueue');
                Helper::removePanel(1, self::$panel);
        }

        /**
         * 获取插件配置面板
         *
         * @access public
         * @param Typecho_Widget_Helper_Form $form 配置面板
         * @return void
         */
        public static function config(Typecho_Widget_Helper_Form $form)
        {
                $options = Typecho_Widget::widget('Widget_Options');
                echo "<a href='https://blog.uniartisan.com/archives/CommentToMail.html'>请在设置前仔细阅读相关说明</a>";

                /* 检查版本更新 */
                if (in_array('check_beta', Helper::options()->plugin('CommentToMail')->other) == true){
                        $newVer = self::check_update("betaVer");
                }
                else{
                        $newVer = self::check_update("newVer");
                }
                
                if (strcmp(self::$version,$newVer) < 0 && $newVer != "Error") {
                        Typecho_Widget::widget('Widget_Notice')->set(_t('请到 https://github.com/uniartisan/CommentToMail 更新插件，当前最新版：' . $newVer), 'success');
                } elseif ($newVer == "Error") {
                        Typecho_Widget::widget('Widget_Notice')->set(_t('对不起, 您的主机不支持 php-curl 扩展或没有打开 allow_url_fopen 功能, 无法自动检测更新'), 'fail');
                } else {
                        Typecho_Widget::widget('Widget_Notice')->set(_t('欢迎 Star, Fork, Pull requests :)'), 'success');
                }

                $mode = new Typecho_Widget_Helper_Form_Element_Radio(
                        'mode',
                        array(
                                'smtp' => 'smtp',
                                'mail' => 'mail()',
                                'sendmail' => 'sendmail()'
                        ),
                        'smtp',
                        '发信方式'
                );
                $form->addInput($mode);

                $host = new Typecho_Widget_Helper_Form_Element_Text(
                        'host',
                        NULL,
                        'smtp.',
                        _t('SMTP地址'),
                        _t('请填写 SMTP 服务器地址')
                );
                $form->addInput($host->addRule('required', _t('必须填写一个SMTP服务器地址')));

                $port = new Typecho_Widget_Helper_Form_Element_Text(
                        'port',
                        NULL,
                        '25',
                        _t('SMTP端口'),
                        _t('SMTP服务端口,一般为25。SSL加密一般为465')
                );
                $port->input->setAttribute('class', 'mini');
                $form->addInput($port->addRule('required', _t('必须填写SMTP服务端口'))
                        ->addRule('isInteger', _t('端口号必须是纯数字')));

                $user = new Typecho_Widget_Helper_Form_Element_Text(
                        'user',
                        NULL,
                        NULL,
                        _t('SMTP用户'),
                        _t('SMTP服务验证用户名,一般为邮箱名如：yourname@domain.com')
                );
                $form->addInput($user->addRule('required', _t('SMTP服务验证用户名')));

                $pass = new Typecho_Widget_Helper_Form_Element_Password(
                        'pass',
                        NULL,
                        NULL,
                        _t('SMTP密码')
                );
                $form->addInput($pass->addRule('required', _t('SMTP服务验证密码')));

                $validate = new Typecho_Widget_Helper_Form_Element_Checkbox(
                        'validate',
                        array(
                                'validate' => '服务器需要验证',
                                'ssl' => 'ssl加密',
                                'tls' => 'tls加密',
                                'solve544' => '启用抄送以规避544错误'
                        ),
                        array('validate'),
                        'SMTP验证'
                );
                $form->addInput($validate);

                $fromName = new Typecho_Widget_Helper_Form_Element_Text(
                        'fromName',
                        NULL,
                        NULL,
                        _t('发件人名称'),
                        _t('发件人名称，留空则使用博客标题')
                );
                $form->addInput($fromName);

                $mail = new Typecho_Widget_Helper_Form_Element_Text(
                        'mail',
                        NULL,
                        NULL,
                        _t('接收邮件的地址'),
                        _t('接收邮件的地址,如为空则使用文章作者个人设置中的邮件地址！')
                );
                $form->addInput($mail->addRule('email', _t('请填写正确的邮件地址！')));

                $contactme = new Typecho_Widget_Helper_Form_Element_Text(
                        'contactme',
                        NULL,
                        NULL,
                        _t('模板中“联系我”的邮件地址'),
                        _t('联系我用的邮件地址,如为空则使用文章作者个人设置中的邮件地址！')
                );
                $form->addInput($contactme->addRule('email', _t('请填写正确的邮件地址！')));

                $titleForOwner = new Typecho_Widget_Helper_Form_Element_Text(
                        'titleForOwner',
                        null,
                        "[{title}] 一文有新的评论",
                        _t('博主接收邮件标题')
                );
                $form->addInput($titleForOwner->addRule('required', _t('博主接收邮件标题 不能为空')));

                $titleForGuest = new Typecho_Widget_Helper_Form_Element_Text(
                        'titleForGuest',
                        null,
                        "您在 [{title}] 的评论有了回复",
                        _t('访客接收邮件标题')
                );
                $form->addInput($titleForGuest->addRule('required', _t('访客接收邮件标题 不能为空')));

                $status = new Typecho_Widget_Helper_Form_Element_Checkbox(
                        'status',
                        array(
                                'approved' => '提醒已通过评论',
                                'waiting' => '提醒待审核评论',
                                'spam' => '提醒垃圾评论'
                        ),
                        array('approved', 'waiting'),
                        '提醒设置',
                        _t('该选项仅针对博主，访客只发送已通过的评论。')
                );
                $form->addInput($status);

                $other = new Typecho_Widget_Helper_Form_Element_Checkbox(
                        'other',
                        array(
                                'to_owner' => '有评论及回复时，发邮件通知博主。',
                                'to_guest' => '评论被回复时，发邮件通知评论者。',
                                'to_me' => '自己回复自己的评论时，发邮件通知。(同时针对博主和访客)',
                                'force_mail' => '强制忽略用户选择，解决回复审核后评论无通知。',
                                'force_wait' => '启用间隔时间以应对反垃圾策略。(建议)',
                                'to_log' => '记录邮件发送日志。(开发模式)',
                                'check_beta' => '检查开发版本（请不要在生产环境使用）'
                        ),
                        array('to_owner', 'to_guest'),
                        '其他设置',
                        _t('由于Typecho钩子限制，开启审核后通过审核会重复通知。')
                );
                $form->addInput($other->multiMode());

                $force_waiting_time = new Typecho_Widget_Helper_Form_Element_Text(
                        'force_waiting_time',
                        NULL,
                        '1',
                        _t('强制间隔的时间'),
                        _t('强制间隔的时间，缺省值为1秒，建议小于3秒,请填入整数时间\n此选项仅在开启时有效')
                );
                $form->addInput($force_waiting_time->addRule('isInteger', _t('请填入整数时间')));

                $entryUrl = ($options->rewrite) ? $options->siteUrl : $options->siteUrl . 'index.php';

                $deliverMailUrl = rtrim($entryUrl, '/') . '/action/' . self::$action . '?do=deliverMail&key=[yourKey]';
                $key = new Typecho_Widget_Helper_Form_Element_Text(
                        'key',
                        null,
                        Typecho_Common::randString(16),
                        _t('key'),
                        _t('执行发送任务地址为（ 请注意：实际地址不包括[ ] ）' . $deliverMailUrl)
                );
                $form->addInput($key->addRule('required', _t('key 不能为空.')));

                $nonAuthUrl = rtrim($entryUrl, '/') . '/commentToMailProcessQueue/';
                $nonAuth = new Typecho_Widget_Helper_Form_Element_Checkbox(
                        'verify',
                        array('nonAuth' => '开启不验证key（仅特殊环境下及调试时使用使用，建议无需求不要勾选，以防被用于恶意消耗服务器资源) ' . $nonAuthUrl),
                        array(),
                        '执行验证'
                );
                $form->addInput($nonAuth);

                $clean_time = new Typecho_Widget_Helper_Form_Element_Select(
                        'clean_time',
                        array(
                                'no_clean' => '不清理',
                                'immediate' => '发送成功后立即清理'
                        ),
                        'no_clean',
                        _t('清理时间'),
                        _t('已发送邮件数据移除的时间')
                );
                $form->addInput($clean_time);
        }

        /**
         *  从服务器获取新版本信息
         *  type: newVer, betaVer
         *  @access public
         */
        public static function check_update($type)
        {
                $api="https://files.uniartisan.com/checkupdate/{$type}.txt";
                $http = Typecho_Http_Client::get();
                $http->setTimeout(3);
                try {
                        $msg = $http->send($api);
                        return $msg;
                } catch (Exception $e) {
                        $msg = 'Error';
                        return $msg;
                }
        }

        /**
         * 个人用户的配置面板
         *
         * @access public
         * @param Typecho_Widget_Helper_Form $form
         * @return void
         */
        public static function personalConfig(Typecho_Widget_Helper_Form $form)
        {
        }

        public static function dbInstall()
        {
                $installDb = Typecho_Db::get();
                $type = explode('_', $installDb->getAdapterName());
                $type = array_pop($type);
                $prefix = $installDb->getPrefix();
                $scripts = file_get_contents('usr/plugins/CommentToMail/' . $type . '.sql');
                $scripts = str_replace('typecho_', $prefix, $scripts);
                $scripts = str_replace('%charset%', 'utf8', $scripts);
                $scripts = explode(';', $scripts);
                try {
                        foreach ($scripts as $script) {
                                $script = trim($script);
                                if ($script) {
                                        $installDb->query($script, Typecho_Db::WRITE);
                                }
                        }
                        return '建立邮件队列数据表，插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                        $code = $e->getCode();
                        if (('Mysql' == $type && 1050 == $code) ||
                                ('SQLite' == $type && ('HY000' == $code || 1 == $code))
                        ) {
                                try {
                                        $script = 'SELECT `id`, `content`, `sent` FROM `' . $prefix . 'mail`';
                                        $installDb->query($script, Typecho_Db::READ);
                                        return '检测到邮件队列数据表，插件启用成功';
                                } catch (Typecho_Db_Exception $e) {
                                        $code = $e->getCode();
                                        if (('Mysql' == $type && 1054 == $code) ||
                                                ('SQLite' == $type && ('HY000' == $code || 1 == $code))
                                        ) {
                                                return Links_Plugin::linksUpdate($installDb, $type, $prefix);
                                        }
                                        throw new Typecho_Plugin_Exception('数据表检测失败，插件启用失败。错误号：' . $code);
                                }
                        } else {
                                throw new Typecho_Plugin_Exception('数据表建立失败，插件启用失败。错误号：' . $code);
                        }
                }
        }

        /**
         * 获取邮件内容
         *
         * @access public
         * @param $comment 调用参数
         * @return void
         */
        public static function parseComment($comment)
        {
                $options = Typecho_Widget::widget('Widget_Options');
                $cfg = array(
                        'siteTitle' => $options->title,
                        'timezone'  => $options->timezone,
                        'cid'       => $comment->cid,
                        'coid'      => $comment->coid,
                        'created'   => $comment->created,
                        'author'    => $comment->author,
                        'authorId'  => $comment->authorId,
                        'ownerId'   => $comment->ownerId,
                        'mail'      => $comment->mail,
                        'ip'        => $comment->ip,
                        'title'     => $comment->title,
                        'text'      => $comment->text,
                        'permalink' => $comment->permalink,
                        'status'    => $comment->status,
                        'parent'    => $comment->parent,
                        'manage'    => $options->siteUrl . __TYPECHO_ADMIN_DIR__ . "manage-comments.php"
                );

                self::$_isMailLog = in_array('to_log', Helper::options()->plugin('CommentToMail')->other) ? true : false;

                //是否接收邮件
                if (isset($_POST['receiveMail']) && 'yes' == $_POST['receiveMail']) {
                        $cfg['banMail'] = 0;
                } else {
                        $cfg['banMail'] = 1;
                        $cfg['banMail'] = in_array('force_mail', Helper::options()->plugin('CommentToMail')->other) ? false : true;
                }


                // 添加至队列
                $cfg      = (object)$cfg;
                $db = Typecho_Db::get();
                $prefix = $db->getPrefix();
                $id = $db->query(
                        $db->insert($prefix . 'mail')->rows(array(
                                'content' => base64_encode(serialize($cfg)),
                                'sent' => '0'
                        ))
                );

                $date = new Typecho_Date(Typecho_Date::gmtTime());
                $time = $date->format('Y-m-d H:i:s');
        }

        /**
         * 通过邮件
         *
         * @access public
         * @param $comment,$edit,$status 调用参数
         * @return void
         */
        public static function passComment($comment, $edit, $status)
        {
                if ('approved' == $status) {
                        $edit->status = 'approved';
                        self::parseComment($edit);
                }
        }
}
