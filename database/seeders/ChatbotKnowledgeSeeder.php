<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use Illuminate\Database\Seeder;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['category' => 'Enrollment', 'question' => 'How do I enroll for the semester?',
             'answer' => 'Log in to your account during the enrollment period shown on the Academic Calendar, then coordinate with your program coordinator for adviser assignment. Enrollment status is confirmed once your fees are settled.'],

            ['category' => 'Applications', 'question' => 'What are the requirements for the Comprehensive Examination?',
             'answer' => 'You need an Application Form, Certificate of Registration, and Payment Receipt. Upload all three when you apply.'],

            ['category' => 'Applications', 'question' => 'How long does application processing take?',
             'answer' => 'Comprehensive Exam: 10 working days. Title Defense: 15 working days. Proposal/Final Defense: 15 working days. Graduation: 20 working days — counted from the day your payment is verified.'],

            ['category' => 'Payments', 'question' => 'How do I pay for an application or document request?',
             'answer' => 'Payments are made directly at the university cashier or through LandBank Link.BizPortal. Upload your receipt or enter the reference number, and staff will verify it.'],

            ['category' => 'Payments', 'question' => 'My payment status still shows "Payment Verification" — what does that mean?',
             'answer' => 'It means your receipt or reference number hasn\'t been checked by staff yet. This usually takes 1-2 working days. You will get a notification once it is verified.'],

            ['category' => 'Documents', 'question' => 'How do I request a Transcript of Records or other document?',
             'answer' => 'Go to Document Requests → New Request, choose the document type, state your purpose, and proceed to payment.'],

            ['category' => 'Account', 'question' => 'I forgot my password — what do I do?',
             'answer' => 'Use the "Forgot password?" link on the login page. A reset link will be sent to your registered university email.'],

            ['category' => 'Account', 'question' => 'Can I update my contact number or address?',
             'answer' => 'Yes — go to Profile and edit your contact number and address directly. Your name, student number, and program can only be changed by the Registrar.'],

            ['category' => 'Graduation', 'question' => 'How do I get added to the graduation candidate list?',
             'answer' => 'Submit a Graduation application with your Comprehensive Exam result and Clearance Form, then pay the graduation fee. Once approved, your ceremony schedule appears in your Application Tracker.'],
        ];

        foreach ($rows as $row) {
            ChatbotKnowledge::create($row);
        }
    }
}