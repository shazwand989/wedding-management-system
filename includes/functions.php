<?php

include_once 'email.php'; // Includes the email.php file, which likely contains email configuration or functions.

function base_url($url = null)
{
    return SITE_URL . $url; // Concatenates the SITE_URL constant with the provided URL parameter to create an absolute URL.
}

function redirect($url)
{
    echo "<script>window.location.href = '$url';</script>"; // Uses JavaScript to redirect the browser to a new URL.
    die(); // Stops the script execution after the redirect.
}

function alert($text, $type)
{
    $msg = "<div class='alert alert-" . $type . " alert-dismissible fade show' role='alert'>
                <strong>" . $text . "</strong>
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                    <span aria-hidden='true'>&times;</span>
                </button>
            </div>"; // Creates an HTML alert message with a close button.
    return $msg; // Returns the generated HTML alert message.
}

function set_flash_message($text, $type = 'info')
{
    $_SESSION['flash_message'] = [
        'text' => $text, // Sets the text of the flash message.
        'type' => $type, // Sets the type of the flash message (e.g., 'info', 'danger').
    ];
}

function display_flash_message()
{
    if (isset($_SESSION['flash_message'])) { // Checks if there is a flash message set in the session.
        $text = $_SESSION['flash_message']['text']; // Gets the text of the flash message.
        $type = $_SESSION['flash_message']['type']; // Gets the type of the flash message.

        echo alert($text, $type); // Displays the alert message.

        unset($_SESSION['flash_message']); // Clears the flash message from the session to ensure it's only displayed once.
    }
}

// function send_email($email, $subject, $body)
// {
//     try {
//         global $mail; // Uses the global $mail variable, which is likely a configured PHPMailer instance.
//         $mail->isSMTP(); // Sets the mailer to use SMTP.
//         $mail->Host = MAIL_HOST; // Sets the SMTP server host.
//         $mail->SMTPAuth = true; // Enables SMTP authentication.
//         $mail->Username = MAIL_USERNAME; // Sets the SMTP username.
//         $mail->Password = MAIL_PASSWORD; // Sets the SMTP password.
//         $mail->SMTPSecure = MAIL_SMTP_SECURE; // Sets the encryption system to use (e.g., 'tls' or 'ssl').
//         $mail->Port = MAIL_PORT; // Sets the SMTP port.

//         $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME); // Sets the sender's email address and name.
//         $mail->addAddress($email); // Adds a recipient's email address.

//         $mail->isHTML(true); // Sets the email format to HTML.
//         $mail->Subject = $subject; // Sets the email subject.
//         $mail->Body = $body; // Sets the email body content.

//         $mail->send(); // Sends the email.
//         return true; // Returns true if the email was sent successfully.
//     } catch (Exception $e) {
//         return $e->getMessage(); // Returns the error message if the email failed to send.
//     }
// }



function uploadImage($file, $path)
{
    if (!file_exists($path)) { // Checks if the specified directory path does not exist.
        mkdir($path, 0777, true); // Creates the directory with full permissions if it does not exist.
    }

    $allowed = ['jpg', 'jpeg', 'png']; // Defines the allowed file extensions.

    $file_name = $file['name']; // Gets the original file name.
    $file_ext = explode('.', $file_name); // Splits the file name to extract the extension.
    $file_ext = strtolower(end($file_ext)); // Converts the file extension to lowercase.

    if (!in_array($file_ext, $allowed)) { // Checks if the file extension is not allowed.
        return false; // Returns false if the file extension is not allowed.
    }

    if ($file['size'] > 2097152) { // Checks if the file size exceeds 2MB.
        return false; // Returns false if the file size is too large.
    }

    $file_name = time() . '_' . $file['name']; // Creates a new file name using the current timestamp and the original file name.
    $file_path = $path . $file_name; // Creates the full file path.
    move_uploaded_file($file['tmp_name'], $file_path); // Moves the uploaded file to the specified directory.
    return $file_name; // Returns the new file name.
}

function deleteImage($file_name, $path)
{
    if (file_exists($path . $file_name) && ($file_name != 'default.png' || $file_name != 'default.jpg')) {
        unlink($path . $file_name); // Deletes the file if it exists and is not the default image.
    }
}

function states()
{
    return [
        'Johor',
        'Kedah',
        'Kelantan',
        'Kuala Lumpur',
        'Labuan',
        'Melaka',
        'Negeri Sembilan',
        'Pahang',
        'Perak',
        'Perlis',
        'Pulau Pinang',
        'Putrajaya',
        'Sabah',
        'Sarawak',
        'Selangor',
        'Terengganu'
    ]; // Returns an array of state names.
}

function send_whatsapp($target, $message, $countryCode = '60')
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $target,
            'message' => $message,
            'countryCode' => $countryCode,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization:' . WHATSAPP_TOKEN
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }
    curl_close($curl);

    if (isset($error_msg)) {
        return $error_msg;
    }
    return $response;
}

function upload_file($file, $directory, $filSize = 2097152)
{
    // check if directory not exists
    if (!file_exists($directory)) {
        mkdir($directory, 0777, true);
    }

    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];

    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

    $valid_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    if (!in_array($file_extension, $valid_extensions)) {
        $data = [
            'status' => 'error',
            'message' => 'Invalid file extension. Only jpg, jpeg, png, pdf, doc, docx, xls, xlsx are allowed.'
        ];
    }

    if ($file_size > $filSize) {
        $data = [
            'status' => 'error',
            'message' => 'File size is too large. File size should be less than' . intval($filSize / 1024) . 'KB'
        ];
    }

    $file_name = time() . '_' . rand(1000, 9999) . '.' . $file_extension;

    move_uploaded_file($file_tmp, $directory . '/' . $file_name);

    $data = [
        'status' => 'success',
        'message' => 'File uploaded successfully.',
        'file_name' => $file_name
    ];

    return $data;
}

