# CommentToMail

**支持的 Typecho 版本 >=1.0**

PHP 推荐版本 PHP7-PHP7.4 ，在 PHP8.1 测试过可以运行，但官方有些接口仍然没有迁移到 PHP8,所以不推荐。

## 版权申明

1. 插件原版本相关信息保留在插件文件的作者信息下方

2. PHP Mailer 文件来自于GitHub

3. 转载或重制请保留作者信息


## 下载地址

[Github Release](https://github.com/uniartisan/CommentToMail/releases)


## 提问的艺术
https://github.com/ryanhanwu/How-To-Ask-Questions-The-Smart-Way/blob/master/README-zh_CN.md

## 使用方法
>https://blog.uniartisan.com/archives/CommentToMail.html




**在反馈任何问题以前，请您认真查看：提问的艺术**



**版权申明**
1.插件原版本及作者相关信息保留在插件文件的作者信息下方
2.[PHP Mailer](https://github.com/PHPMailer/PHPMailer/) 文件来自于 GitHub
3.转载或重制请保留作者信息

## 使用方法

1.下载插件，将插件上传到 /usr/plugins/ 目录下，修改主题模板comments.php文件，在评论form表单的适当位置添加name为receiveMail的选择框（checkbox），请注意：下方两种代码，你只能选择一个添加到主题模板文件，一般建议你选择默认接收邮件。**如果您在插件设置中开启强制忽略用户选择，您可以跳过这一步。**

- 正常显示选择框：
  `<input type="checkbox" name="receiveMail" id="receiveMail" value="yes" checked /> <label for="receiveMail" style="padding-left:8px;">当有人回复时接收邮件提醒</label>`
- 隐藏选择框（默认接受邮件）：
  `<input type="hidden" name="receiveMail" id="receiveMail" value="yes" />`

下面我以handsome主题作为例子：
![IMG_0301.PNG][2]
选中第二个文件夹，找到comments.php
![IMG_0302.PNG][3]
定位到下图所示位置：
![IMG_0285.JPG][4]
在上图主题文件评论框的input下方插入代码即可（任意一个input都行，不过为了方便，可以添加在邮件那行下方），不过每次主题更新后可能需要重新设置。
（请注意：Handsome主题自4.1.x版本开始，增添对本插件的支持，无需再次修改文件！）
![IMG_0303.PNG][5]
设置完如上图所示，保存好文件！到这一步，你已经成功了一半。
2.后台启用相关插件
3.设置smtp服务器地址、邮箱地址、密码等信息
4.设置cron监控（如果你觉得麻烦或者不会可以添加网址监控！具体步骤参照步骤5）
监控的网址就是插件设置后台的任务执行地址加上你自己设置的Key（注意，任务执行链接不包含【 】，如：http://baidu.com/index.php/action/comment-to-mail?do=deliverMail&key=123456
![IMG_0281.PNG][6]
5.网址监控：在阿里/360网址监控加上你的执行网址就可以发信！在这里我用360网址监控作为演示。（此步骤可代替步骤4）
![IMG_0282.PNG][7]
设置好了会显示如下信息：
![IMG_0283.PNG][8]
正确设置后，就可以正常发信了。360默认每10分钟触发一次，也就是每10分钟将之前的邮件发送一次的意思。
![IMG_0287.PNG][9]

如果你正常设置本插件，但在发信时出现DT:SPM CODE 544错误，你可以到CommentToMail目录下找到Action.php,定位到316行，去除代码的注释。
（此操作仅针对出现错误的用户，如果你发信正常，请不要去除注释！）
![IMG_0288.PNG][10]

## 常见问题

1.Key是邮件任务执行密码，防止他人恶意执行任务消耗资源

2.下方任务执行地址就是说当你访问这个网址时，邮件任务才会执行，为了达到自动发送的效果，我们设置cron或者网址监控，每隔一段时间让远程服务器代替你访问任务执行网址

3.执行验证是用来调试和应对特殊环境，一般不要勾选！

4.可以清理邮件发送信息

5.QQ邮箱smtp密码需要在邮箱网页端获取，具体配置信息可以参考度娘

6.测试普通QQ邮箱可以正常发送，但可能由于腾讯反垃圾邮件逻辑，用户不能正常接受邮件，建议大家使用QQ域名邮箱，如果你没有域名邮箱，可以通过邮件联系我，或者直接在下方留言

7.本插件仅支持typecho1.0及之后版本

8.如果出现 邮件发送失败：SMTP connect() failed. （PHP>=5.6）可以参考这篇博文 https://9sb.org/45

## 写在最后

---

**在反馈任何问题以前，请您认真查看：提问的艺术**

[How-To-Ask-Questions-The-Smart-Way](https://github.com/ryanhanwu/How-To-Ask-Questions-The-Smart-Way/blob/master/README-zh_CN.md
)

---

[1]: https://krait.cn/
[2]: https://blog.uniartisan.com/usr/uploads/2017/10/3483950311.png
[3]: https://blog.uniartisan.com/usr/uploads/2017/10/1923621872.png
[4]: https://blog.uniartisan.com/usr/uploads/2017/10/4292525936.jpg
[5]: https://blog.uniartisan.com/usr/uploads/2017/10/2980327494.png
[6]: https://blog.uniartisan.com/usr/uploads/2017/10/2199260941.png
[7]: https://blog.uniartisan.com/usr/uploads/2017/10/2123489929.png
[8]: https://blog.uniartisan.com/usr/uploads/2017/10/3967795832.png
[9]: https://blog.uniartisan.com/usr/uploads/2017/10/1972513749.png
[10]: https://blog.uniartisan.com/usr/uploads/2017/12/2407010643.png
