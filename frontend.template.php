<?php
/*
input:

User $user - User object
array $config - config array
array $emails - array of emails
*/

require_once './autolink.php';

// Load HTML Purifier
$purifier_config = HTMLPurifier_Config::createDefault();
$purifier_config->set('HTML.Nofollow', true);
$purifier_config->set('HTML.ForbiddenElements', array("img"));
$purifier = new HTMLPurifier($purifier_config);

\Moment\Moment::setLocale($config['locale']);

$mailIds = array_map(function ($mail) {
    return $mail->id;
}, $emails);
$mailIdsJoinedString = filter_var(join('|', $mailIds), FILTER_SANITIZE_SPECIAL_CHARS);

function niceDate($date) {
    $m = new \Moment\Moment($date, date_default_timezone_get());
    return $m->calendar();
}
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css"
          integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB"
          crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.13/css/all.css"
          integrity="sha384-DNOHZ68U8hZfKXOrtjWvjxusGo9WQnrNx2sqG0tfsghAvtVlRW3tvkXWZh58N9jp"
          crossorigin="anonymous">
    <title><?php
        echo $emails ? "(" . count($emails) . ") " : "";
        echo $user->address ?> - Bhadoo Mail</title>
    <link rel="stylesheet" href="//bhadoomail.com/mail/spinner.css">
    <link rel="stylesheet" href="//bhadoomail.com/mail/style.css">
    <link rel="icon" href="//bhadoomail.com/mail/favicon.ico">
    
    <script>
        var mailCount = <?php echo count($emails)?>;
        setInterval(function () {
            var r = new XMLHttpRequest();
            r.open("GET", "./json-api.php?action=has_new_messages&address=$<?php echo $user->address?>&email_ids=<?php echo $mailIdsJoinedString?>", true);
            r.onreadystatechange = function () {
                if (r.readyState != 4 || r.status != 200) return;
                if (r.responseText > 0) {
                    console.log("There are", r.responseText, "new mails.");
                    document.getElementById("new-content-avalable").style.display = 'block';

                    // If there are no emails displayed, we can reload the page without looing any state.
                    if (mailCount === 0) {
                        location.reload();
                    }
                }
            };
            r.send();

        }, 15000);

    </script>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-104532450-2"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-104532450-2');
</script>

</head>
<body>
<center>
<br>
<div style:"background-color: #ff715e;">
<a href="/" ><img border="0" alt="Bhadoo Mail" src="//bhadoomail.com/mail/logo.png" width="193" height="26"></a>
</div>
</center>

<div id="new-content-avalable">
    <div class="alert alert-info alert-fixed" role="alert">
        <strong>New emails</strong> have arrived.

        <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
            <i class="fas fa-sync"></i>
            Reload!
        </button>

    </div>
    <!-- move the rest of the page a bit down to show all content -->
    <div style="height: 3rem">&nbsp;</div>
</div>

<header>
    <div class="container">
        <p class="lead">
            Your Disposable Mailbox is Ready.
        </p>
        <p>
            Currently Fetching Emails from Google Gmail Service via IMAP.
        </p>
        <div class="row" id="address-box-normal">

            <div class="col my-address-block">
                <span id="my-address"><?php echo $user->address ?></span>&nbsp;<button class="copy-button" data-clipboard-target="#my-address">Copy</button>
            </div>


            <div class="col get-new-address-col">
                <button type="button" class="btn btn-outline-dark"
                        data-toggle="collapse" title="choose your own address"
                        data-target=".change-address-toggle"
                        aria-controls="address-box-normal address-box-edit" aria-expanded="false">
                    <i class="fas fa-magic"></i> Change address
                </button>
            </div>
        </div>


        <form class="collapse change-address-toggle" id="address-box-edit" action="?action=redirect" method="post">
            <div class="card">
                <div class="card-body">
                    <p>
                        <a href="?action=random" role="button" class="btn btn-dark">
                            <i class="fa fa-random"></i>
                            Open random mailbox
                        </a>
                    </p>


                    or create your own address:
                    <div class="form-row align-items-center">
                        <div class="col-sm">
                            <label class="sr-only" for="inlineFormInputName">username</label>
                            <input name="username" type="text" class="form-control" id="inlineFormInputName"
                                   placeholder="username"
                                   value="<?php echo $user->username ?>">
                        </div>
                        <div class="col-sm-auto my-1">
                            <label class="sr-only" for="inlineFormInputGroupUsername">Domain</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">@</div>
                                </div>

                                <select class="custom-select" id="inlineFormInputGroupUsername" name="domain">
                                    <?php
                                    foreach ($config['domains'] as $aDomain) {
                                        $selected = $aDomain === $user->domain ? ' selected ' : '';
                                        print "<option value='$aDomain' $selected>$aDomain</option>";
                                    }
                                    ?>
                                </select>


                            </div>
                        </div>
                        <div class="col-auto my-1">
                            <button type="submit" class="btn btn-primary">Open mailbox</button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</header>

<main>
    <div class="container">

        <div id="email-list" class="list-group">

            <?php
            foreach ($emails

                     as $email) {
                $safe_email_id = filter_var($email->id, FILTER_VALIDATE_INT);
                ?>

                <a class="list-group-item list-group-item-action email-list-item" data-toggle="collapse"
                   href="#mail-box-<?php echo $email->id ?>"
                   role="button"
                   aria-expanded="false" aria-controls="mail-box-<?php echo $email->id ?>">

                    <div class="media">
                        <button class="btn btn-white open-collapse-button">
                            <i class="fas fa-caret-right expand-button-closed"></i>
                            <i class="fas fa-caret-down expand-button-opened"></i>
                        </button>


                        <div class="media-body">
                            <h6 class="list-group-item-heading"><?php echo filter_var($email->fromName, FILTER_SANITIZE_SPECIAL_CHARS) ?>
                                <span class="text-muted"><?php echo filter_var($email->fromAddress, FILTER_SANITIZE_SPECIAL_CHARS) ?></span>
                                <small class="float-right"
                                       title="<?php echo $email->date ?>"><?php echo niceDate($email->date) ?></small>
                            </h6>
                            <p class="list-group-item-text text-truncate" style="width: 75%">
                                <?php echo filter_var($email->subject, FILTER_SANITIZE_SPECIAL_CHARS); ?>
                            </p>
                        </div>
                    </div>
                </a>


                <div id="mail-box-<?php echo $email->id ?>" role="tabpanel" aria-labelledby="headingCollapse1"
                     class="card-collapse collapse"
                     aria-expanded="true">
                    <div class="card-body">
                        <div class="card-block email-body">
                            <div class="float-right primary">
                                <a class="btn btn-outline-primary btn-sm" download="true"
                                   role="button"
                                   href="<?php echo "?action=download_email&email_id=$safe_email_id&address=$user->address" ?>">
                                    Download
                                </a>

                                <a class="btn btn-outline-danger btn-sm"
                                   role="button"
                                   href="<?php echo "?action=delete_email&email_id=$safe_email_id&address=$user->address" ?>">
                                    Delete
                                </a>
                            </div>

                            <?php
                            $safeHtml = $purifier->purify($email->textHtml);

                            $safeText = htmlspecialchars($email->textPlain);
                            $safeText = nl2br($safeText);
                            $safeText = \AutoLinkExtension::auto_link_text($safeText);

                            $hasHtml = strlen(trim($safeHtml)) > 0;
                            $hasText = strlen(trim($safeText)) > 0;

                            if ($config['prefer_plaintext']) {
                                if ($hasText) {
                                    echo $safeText;
                                } else {
                                    echo $safeHtml;
                                }
                            } else {
                                if ($hasHtml) {
                                    echo $safeHtml;
                                } else {
                                    echo $safeText;
                                }
                            }
                            ?>

                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php
            if (empty($emails)) { ?>
                <div id="empty-mailbox">
                    <p>Emails will appear here automatically. <a href=""><strong>Refresh</strong></a> page if you expect an email right now.</p>
                    <div class="spinner">
                        <div class="rect1"></div>
                        <div class="rect2"></div>
                        <div class="rect3"></div>
                        <div class="rect4"></div>
                        <div class="rect5"></div>
                    </div>
                </div>
            <?php } ?>
        </div>
</main>

<footer>
    <div class="container">


        <!--        <select id="language-selection" class="custom-select" title="Language">-->
        <!--            <option selected>English</option>-->
        <!--            <option value="1">Deutsch</option>-->
        <!--            <option value="2">Two</option>-->
        <!--            <option value="3">Three</option>-->
        <!--        </select>-->
        <!--        <br>-->

        <small class="text-justify quick-summary">
            This page reloads slow due to Shared Hosting Resources.
        </small><br>
        <small class="text-justify quick-summary">
            This is a disposable mailbox service. Whoever knows your username, can read your emails.
            Emails will be deleted after 30 days, Yes! expecting it to balance load of 30 days.
            <a data-toggle="collapse" href="#about"
               aria-expanded="false"
               aria-controls="about">
                Show Details
            </a>
        </small>
        <div class="card card-body collapse" id="about" style="max-width: 40rem">

            <p class="text-justify">This disposable mailbox keeps your main mailbox clean from spam.</p>

            <p class="text-justify">Just choose an address and use it on websites you don't trust and
                don't
                want to use
                your
                main email address.
                Once you are done, you can just forget about the mailbox. All the spam stays here and does
                not
                fill up
                your
                main mailbox.
            </p>

            <p class="text-justify">
                You select the address you want to use and received emails will be displayed
                automatically.
                There is not registration and no passwords. If you know the address, you can read the
                emails.
                <strong>Basically, all emails are public. So don't use it for sensitive data.</strong>


            </p>
        </div>

        <p>
            <small>Powered by
                <a href="https://github.com/synox/disposable-mailbox"><strong>synox/disposable-mailbox</strong></a> and 
                <a href="https://bhadoomail.com"><strong>bhadoomail.com</strong></a>. Support at hashhackers@gmail.com.
            </small>
        </p>
    </div>
</footer>


<!-- jQuery first, then Popper.js, then Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"
        integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>

<script>
    clipboard = new ClipboardJS('[data-clipboard-target]');
    $(function () {
        $('[data-tooltip="tooltip"]').tooltip()
    });

    /** from https://github.com/twbs/bootstrap/blob/c11132351e3e434f6d4ed72e5a418eb692c6a319/assets/js/src/application.js */
    clipboard.on('success', function (e) {
        $(e.trigger)
            .attr('title', 'Copied!')
            .tooltip('_fixTitle')
            .tooltip('show')
            .tooltip('_fixTitle');
        e.clearSelection();
    });

</script>

</body>
</html>
