<?php

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

$errMsg = "";

$client = new S3Client([
    'credentials' => [
        'key'    => "",
        'secret' => ""
    ],
    'region' => "eu-west-2",
    'version' => 'latest'
]);

$adapter = new AwsS3Adapter($client, 'demo');
$filesystem = new Filesystem($adapter);

$careers = new Career();
if (isset($_GET["id"])) {
    $careers->Get($_GET["id"]);
}

$department="General";
if (isset($_GET["department"])) {
    $department = htmlspecialchars($_GET["department"]);
}

if(isset($_POST['submit']) && !empty($_POST['submit'])){
    if(isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        //your site secret key
        $secret = 'SECRET_KEY';
        //get verify response data
        $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
        $responseData = json_decode($verifyResponse);

        if ($responseData->success) {
            //contact form submission code
            if (isset($_FILES['userfile']))
            {
                $errors = array();

                $target_dir = "uploads/";
                $target_file = $target_dir . basename($_FILES["userfile"]["name"]);

                $file_name = $_FILES['userfile']['name'];
                $file_tmp = $_FILES['userfile']['tmp_name'];
                $file_type = $_FILES['userfile']['type'];

                $uniqid = uniqid("CV-");

                $expensions = array("doc", "pdf", "docx", "pages");

                $FileType = pathinfo($_FILES["userfile"]["name"], PATHINFO_EXTENSION);

                if ($_FILES['userfile']['size'] > 2097152) {
                    $errMsg = "<span style='color:red;'><b>File size must be less than 2 MB</b></span><br><br>";
                }

                if (empty($errors) == true)
                {
                    $cv = "/uploads/" . $uniqid . "." . $FileType;
                    move_uploaded_file($file_tmp, $_SERVER['DOCUMENT_ROOT'] . $cv);

                    $stream = fopen($_SERVER['DOCUMENT_ROOT'] . $cv, 'r');
                    $id = $filesystem->writeStream('CV/' . $uniqid . "." . $FileType, $stream);

                    $candidate = new JobApplication();
                    $candidate->Apply($_GET["id"],$_POST["firstname"],$_POST["lastname"],$_POST["emailaddress"],$_POST["phone"],'CV/'.$uniqid.".".$FileType);
                    $errMsg = "<span style='color:green;'><b>Your cv has been submitted. We will be in touch shortly</b></span><br><br>";

                    //unlink($_SERVER['DOCUMENT_ROOT'] . $cv);

                }
            }
        } else {
            $errMsg = "<span style='color:red;'><b>Robot verification failed, please try again.</b></span><br><br>";
        }
    } else {
        $errMsg = "<span style='color:red;'><b>Please click on the reCAPTCHA box.</b></span><br><br>";
    }
}

?>
<!DOCTYPE html>
<!--[if IE 9]><html class="ie ie9"> <![endif]-->
<html>
<head>
    <title>Careers</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT']."/resources/careers/head.php" ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<?php include_once $_SERVER['DOCUMENT_ROOT']."/resources/careers/header.php"; ?>
<div class="parallax inner-head" style="height: 150px;">
    <div class="container">
        <div class="row" style="padding-top: 50px;">
            <div class="col-md-12">
                <i class="fa fa-list-ol"></i>
                <h4><?php echo $careers->title ?></h4>
                <ol class="breadcrumb">
                    <li><a href="/">Home</a></li>
                    <li><a href="/careers/">Jobs</a></li>
                    <li><?php echo $careers->department ?></li>
                    <li class="active"><?php echo $careers->title ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<div class="contain-wrapp padding-bot40">
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-sm-7">
                <h5 class="head-title"><?php echo $careers->title ?></h5>
                <br>
                <table width="100%"><tr>
                        <td><strong>Type:</strong> <?php echo $careers->careertype ?></td>
                        <td>
                            <strong>Location:</strong> <?php echo $careers->location ?>
                        </td><td><strong>Department:</strong> <?php echo $careers->department ?></td></tr></table><br>
                <p>
                    <b>Description:</b><br>
                    <?php echo $careers->description ?><br><br>
                    <b>Role and Responsibilities:</b>
                    <?php echo $careers->role ?><br>
                    <b>Your profile:</b><br>
                    <?php echo $careers->profile ?><br>
                    <b>What we offer: </b>
                    <?php echo $careers->offer ?>
                </p>
            </div>
            <div class="col-md-4 col-sm-5">
                <aside>
                    <div class="order-detail">
                        <div class="widget">
                            <h5 class="widget-head">Apply Now</h5>
                            <form id="careerapplication" action="career.php?id=<?php echo $careers->id ?>" method="post" enctype="multipart/form-data">
                                <div class="box_style_1">
                                    <?php echo $errMsg ?>
                                    <div class="form-group">
                                        <label>Your First name</label>
                                        <input type="text" id="firstname" name="firstname" class="input-md input-rounded form-control" placeholder="Your First name" maxlength="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Your Last name</label>
                                        <input type="text" id="lastname" name="lastname" class="input-md input-rounded form-control" placeholder="Your Last name" maxlength="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Your Email address</label>
                                        <input type="email" id="emailaddress" name="emailaddress" class="input-md input-rounded form-control" placeholder="Your Email address" maxlength="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Your Contact number</label>
                                        <input type="text" id="phone" name="phone" class="input-md input-rounded form-control" placeholder="Your Contact number" maxlength="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Your CV (less than 2 MB)</label>
                                        <input type="file" name="userfile" class="input-md input-rounded form-control" maxlength="100" required>
                                    </div>
                                    <div class="g-recaptcha" data-sitekey="6LeUTkcUAAAAAMCAHeMXMwB4-H4Anx1fMyNoTQP_"></div>
                                    <br>
                                    Your privacy is important to us. We understand when applying for a job, you are putting your confidence in us that we will use your personal data correctly and sensitively and is absolutely our main priority.
                                    In the interests of transparency, here is a link to what information we may store about you during the recruitment process <a href="/datapolicy.php">Click here</a>
                                    <br><br>
                                    <button id="submit" name="submit" value="submit" type="submit" class="btn btn-duckegg" style="background: #8EC63F; color: white">Apply For This Position</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>
<div class="clearfix"></div>
<?php include_once $_SERVER['DOCUMENT_ROOT']."/resources/careers/footer.php" ?>
<?php include_once $_SERVER['DOCUMENT_ROOT']."/resources/careers/scripts.php" ?>
</body>
</html>