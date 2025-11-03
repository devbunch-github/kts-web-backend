<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['key'=>'customer_welcome','title'=>'Customer Welcome Email','subject'=>'Welcome to {CustomerName}','body'=>'<p>Hi {CustomerName},</p><p>Welcome to {BusinessName}!</p>','default_status'=>true],
            ['key'=>'appointment_new','title'=>'Customer New Appointment Email','subject'=>"You're booked in!",'body'=>'<p>Your appointment is on {AppointmentDate} at {AppointmentTime}</p>','default_status'=>true],
            ['key'=>'appointment_reminder','title'=>'Customer Remind Appointment Email','subject'=>'Your upcoming appointment','body'=>'<p>Reminder: {AppointmentDate} {AppointmentTime}</p>','default_status'=>true],
            ['key'=>'appointment_review','title'=>'Customer Review Appointment Email','subject'=>'Your recent appointment','body'=>'<p>Please leave a review!</p>','default_status'=>true],
            ['key'=>'appointment_move','title'=>'Customer Move Appointment Email','subject'=>'Your appointment has been made','body'=>'<p>Your appointment was rescheduled to {AppointmentDate} {AppointmentTime}</p>','default_status'=>true],
            ['key'=>'appointment_confirm','title'=>'Customer Confirm Appointment Email','subject'=>'Your appointment has been confirmed','body'=>'<p>See you on {AppointmentDate}!</p>','default_status'=>true],
            ['key'=>'appointment_cancel','title'=>'Customer Cancel Appointment Email','subject'=>'Your appointment has been cancelled','body'=>'<p>We’re sorry to see you cancel.</p>','default_status'=>true],
            ['key'=>'birthday','title'=>'Customer Birthday Message Email','subject'=>'Happy Birthday!','body'=>'<p>Happy Birthday {CustomerName}! 🎉</p>','default_status'=>true],
            ['key'=>'order_receipt','title'=>'Customer Order Email','subject'=>'Your Recent Sale','body'=>'<p>Thanks for your purchase #{OrderNumber}</p>','default_status'=>false],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(['key'=>$t['key']],$t);
        }
    }
}
