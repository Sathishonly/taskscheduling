<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use MongoDB\BSON\UTCDateTime;


class Inactiveusers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inactive:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send account removed email to inactive users who have not accessed account for 30 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $inactivityPeriod = 30;
        $cutoffDate = Carbon::now()->subDays($inactivityPeriod);

        $inactiveUsers = User::where('lastlogin', '<=', $cutoffDate)
            ->get();

        foreach ($inactiveUsers as $user) {
            $email = $user->email;
            $emailSent = $user->emailsent;
            if (!$emailSent) {
                try {
                    $user = User::where('email', $email)->first();
                    $user->emailsent = new UTCDateTime(now()->timestamp * 1000);
                    $user->save();

                    require base_path("vendor/autoload.php");
                    $mail = new PHPMailer(true);
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; //  smtp host
                    $mail->SMTPAuth = true;

                    $mail->Username = 'example@gmail.com'; //  sender username
                    $mail->Password = 'password'; // sender password

                    $mail->SMTPSecure = 'tls'; // encryption - ssl/tls
                    $mail->Port = 587; // port - 587/465

                    $mail->setFrom('example@gmail.com', 'Account Removed Due to Inactivity');

                    //$mail->addCC($request->emailCc);
                    //$mail->addBCC($request->emailBcc);

                    //   $mail->addReplyTo('sender-reply-email', 'sender-reply-name');

                    $mail->isHTML(true); // Set email content format to HTML

                    $mail->Subject = 'Account Removed Due to Inactivity';
                    $mail->Body = '<html>
                                            <head>
                                                <style>
                                                    @import url("https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;500;600;700&display=swap");
                                                </style>
                                            </head>
                                            <body>
                                                <div style="width:65%; padding: 10px;">
                                                    <div>
                                                        <section  style="background-color: white;">
                                                        <p style="font-size:12pt; margin: 15px 0px 0px 0px; font-family: \'Assistant\', sans-serif;" class="mt-5">Hi ' . $user->name . ',</p>
                                                        <p style="font-size:12pt; margin: 15px 0px 0px 0px; font-family: \'Assistant\', sans-serif;">We noticed that you have not accessed your account for the past 30 days so your account is removed from our server.</p>
                                                        <p style="font-size:12pt; margin: 15px 0px 0px 0px; font-family: \'Assistant\', sans-serif;">If you have any questions or concerns, feel free to contact us at example@gmail.com.</p>
                                                        <p style="font-size:12pt;margin: 15px 0px 0px 0px;  font-family: \'Assistant\', sans-serif;">Best regards,</p>
                                                        <p style="font-size:12pt;margin:0px; padding-top: 0px; font-family: \'Assistant\', sans-serif;">Team</p>
                                                    </section>
                                                        </section>
                                                        <footer">
                                                            <span style="font-size:12pt;margin: 0px; font-family: \'Assistant\', sans-serif;"><a style="color:blue;" target="_blank" href="www.google.com">www.google.com</a> </span>
                                                        </footer>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';

                    $mail->AddAddress($email);
                    $mail->send();

                } catch (\Exception $e) {
                    return response()->json([
                        'error' => 'Failed to send OTP',
                        'error_code' => $e->getMessage(),
                    ], 500);
                }
            }
        }
    }
}
