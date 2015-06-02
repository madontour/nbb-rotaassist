<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        require_once '../contxt/madonapps.inc';                // sets environment Variables
        require_once '../common/phpmailer/class.phpmailer.php';
        require_once '../common/phpmailer/class.smtp.php';     // set up mail extensions
        
        require_once '../common/mrbs/mrbs_periodnames.inc';    // sets period names
        require_once '../common/mrbs/mrbs_functions.inc';      // define useful functions
        require_once '../contxt/mrbs_dbconnect.inc';           // set dbconnect strings

        require_once './RidersNotifyEmptyShifts.ini';          // default params & constants
/*
  -------------------------------------------------------------------------------------        
         Real code starts here
  --------------------------------------------------------------------------------------
*/
        // get midnight today and midnight tomorrow as seconds  
        $ReportType="Riders";
        $daystoreport = REPORTDAYS;
        $ShiftCount=0;
        unset($LineOfText);
        $msgtxt ="";
        $yr=date("Y"); 
        $mo=date("n");
        $da=date("j");
        $dow=date("w");
        $lag = 6 - $dow;
        $TodaySecs=mktime(0, 0, 0, $mo, $da, $yr); 
        $StartSecs=mktime(0, 0, 0, $mo, $da+$lag, $yr);
        $msgtxt = $msgtxt   . "<strong>NBB Rota Report </strong><br><br> " 
                                ." Available Shifts report for $ReportType: starting "
                                . date("l d/m/y",$StartSecs)
                                . "<br><hr><br><br>";
        echo $msgtxt;
        $LineOfText[]=$msgtxt;
        
        for($nod=0;$nod<=$daystoreport;$nod++){              
            $StartSecs=mktime(0, 0, 0, $mo, $da+$lag+$nod, $yr);       
            $EndSecs=mktime(23, 59, 59, $mo, $da+$lag+$nod, $yr);
                      
// Get available shifts for this day and Report Type
            unset($availableshifts);
            $availableshifts = GetAvailableShifts(substr($ReportType,0,1),$StartSecs);
            
// connect to the database
            $conn = new mysqli($DBServer, $DBUser, $DBPass, $DBName);

// check database connection
            if ($conn->connect_error) {
                trigger_error('Database connection failed: '  . $conn->connect_error, E_USER_ERROR);
            }
// Get record set
            $sql='SELECT start_time, name, type FROM mrbs_entry '
                    . 'WHERE (start_time >= '.$StartSecs. ' AND start_time <' . $EndSecs .') '
                    . 'ORDER BY start_time';
            $rs=$conn->query($sql);

            if($rs === false):
                trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->error, E_USER_ERROR);
            else:
                $rows_returned = $rs->num_rows;
            endif;
            
// iterate over record set and do work            
            
            $rs->data_seek(0);
            while($row = $rs->fetch_assoc()){
                $shiftnum=GetShiftNum($StartSecs,$row['start_time']);
                $availableshifts["$shiftnum"]="taken";
                unset($availableshifts["$shiftnum"]);
                }
            if (count($availableshifts)>0):
                $ShiftCount++ ;
                echo "<strong>" .date("l jS-M-Y",$StartSecs) ."</strong><br>";
                $LineOfText[]="<strong>" .date("l jS-M-Y",$StartSecs) ."</strong><br>";
                foreach($availableshifts as  $FreeShiftName){
                    echo " - ". $FreeShiftName . "<br>";
                    $LineOfText[]=" - ". $FreeShiftName . "<br>";
                }
                echo "<br>";
                $LineOfText[]="<br>";
            endif;
           
// next day in loop
        }
        
// Message complete - held in $LineOfText()
// Now prepare and send email.
      
 
    if ($ShiftCount > 0):
        $mail = new PHPMailer();  // defaults to using php "mail()"
        require_once '../contxt/mrbs_smtpconnect.inc';    // set defaults for googlemail 
        
        
        if (isset($recipients)) :
            foreach($recipients as $val) {
                $mail->addAddress($val);         // Add a recipient
            }
        endif;
        
        if (isset($copies)) :
            foreach($copies as $val) {
                $mail->addCC($val);             // Add a recipient CC
            }
        endif;
        
        if (isset($blinds)) :
            foreach($blinds as $val) {
                $mail->addBCC($val);            // Add a recipient BCC
            }
        endif;  
        $blindsxs = GenerateEmailRecipients(substr($ReportType,0,1));
        if (isset($blindsxs)) :
            foreach($blindsxs as $val) {
                #$mail->addBCC($val);            // Add a recipient BCC
            }
        endif;  
 
        $mail->Subject = MAILSUBJECT ;           // Add subject
        
        $msgtxt="";
        foreach ($LineOfText as $LOT){
            $msgtxt .= $LOT;
        }        
        $mail->Body = $msgtxt;

        if(!$mail->Send()):
            echo "Mailer Error: " . $mail->ErrorInfo;
        else:
            echo "Message sent!";
        endif;
        
    endif;  

        
        ?>
    </body>
</html>
