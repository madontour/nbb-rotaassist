<!DOCTYPE html>
<!--
This script generates an email to alert the committee
if the day after tomorrow has no volunteers at all 
The alert is embedded in an email and sent to a mail list held in ~.ini
The script is designed to be run by cron daily at 013:00

-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
         <?php
        require_once '../common/phpmailer/class.phpmailer.php';
        require_once '../common/phpmailer/class.smtp.php';
        
        require_once '../common/mrbs/mrbs_periodnames.inc';    // sets period names
        require_once '../common/mrbs/mrbs_functions.inc';      // define useful functions
        require_once '../contxt/mrbs_dbconnect.inc';           // set dbconnect strings

        require_once './CommitteeAlertOnNoVolunteers.ini';     // default params & constants
/*
  -------------------------------------------------------------------------------------        
         Real code starts here
  --------------------------------------------------------------------------------------
*/
        // get midnight today and midnight tomorrow as seconds   
        $msgtxt ="";
        $yr=date("Y"); 
        $mo=date("n");
        $da=date("j");
        $dow=date("w");
        $NumOfVolunteers = 0;                
        $StartSecs=mktime(0, 0, 0, $mo, $da+2, $yr);       
        $TodaySecs=mktime(0, 0, 0, $mo, $da, $yr);
        $EndSecs=mktime(23, 59, 59, $mo, $da+2, $yr);
        #echo $da." ".$mo." ".$yr." ".$StartSecs." ".$EndSecs;
        #echo "today is " . '<br>';
        #echo "today is " . date("d/m/y");
        $msgtxt = $msgtxt   . "<strong>NBB Rota ALERT </strong><br><br> " 
                            ." There are currently NO RIDERS OR DRIVERS on the rota for "
                            . date("l d/m/y",$StartSecs)
                            . "<br><br><hr><br><br>"
                            . "Message generated automatically at ".date("d/m/y H:i")."<br>";
        // connect to the database

        $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);
 
        // check connection
        if ($conn->connect_error) {
            trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
        }
        // Get record set
        $sql='SELECT start_time, name, type FROM mrbs_entry '
                . 'WHERE (start_time >= '.$StartSecs. ' AND start_time <' . $EndSecs .') '
                . 'ORDER BY start_time';
        $rs=$conn->query($sql);
 
        if($rs === false) {
            trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
        } else {
            $rows_returned = $rs->num_rows;
        }
        #echo 'Num of Rows '.$rows_returned.'<br>';
        // iterate over record set
        $rs->data_seek(0);
        while($row = $rs->fetch_assoc()){
            $shifttype=GetShiftType($row['type']);
            if ($shifttype <> 'Coordinator') {
                $NumOfVolunteers++;
            }
        }
        
        // echo $msgtxt. '<hr>';
        
        /* 
         * $msgtxt has the info
         * Now Create and Send Email
         */
 
        if ($NumOfVolunteers == 0):
        $mail = new PHPMailer();  // defaults to using php "mail()"
        require_once '../contxt/mrbs_smtpconnect.inc';    // set defaults for googlemail 
        // if recipients is set then add recipients
        
        if (isset($recipients)) :
            foreach($recipients as $val) {
                $mail->addAddress($val);        // Add a recipient
            }
        endif;
        
        if (isset($copies)) :
            foreach($copies as $val) {
                $mail->addCC($val);        // Add a recipient
            }
        endif;
        
        if (isset($blinds)) :
            foreach($blinds as $val) {
                $mail->addBCC($val);        // Add a recipient
            }
        endif;  
 
        $mail->Subject = MAILSUBJECT . date("l d/m/y",$StartSecs) ;           // Add subject
        $mail->Body    = $msgtxt;

        if(!$mail->Send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
        endif;

//$mail->addAddress('ellen@example.com');               // Name is optional
//$mail->addReplyTo('info@example.com', 'Information');
//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
//$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        ?>
    </body>
</html>
