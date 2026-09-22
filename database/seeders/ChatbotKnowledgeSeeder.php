<?php

namespace Database\Seeders;

use App\Models\ChatbotKnowledge;
use App\Http\Controllers\ChatbotController;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ChatbotKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        // Use reflection to call the private getEmbedding method from ChatbotController
        $controller = new ChatbotController();
        $reflector  = new \ReflectionMethod($controller, 'getEmbedding');
        $reflector->setAccessible(true);

        $rows = [
 
            // ================= GENERAL =================
            ['category' => 'General', 'question' => 'What are OUS office hours?',
             'answer' => 'The Open University Systems Office is available Monday to Saturday, 8:00 AM to 5:00 PM, for all services.'],
 
            [
            'category' => 'Payments',
            'question' => 'How do I pay for OUS services?',
            'answer'   => "You can pay for OUS services through two main channels:

            1. **Online Payment:** Via [LandBank's Link.BizPortal](https://www.lbp-eservices.com/egps/portal/index.jsp) (Select **PANGASINAN STATE UNIVERSITY - LINGAYEN** as the Merchant Name).
            2. **In-Person Payment:** Pay directly at the Cashier's Office on campus.

            After paying, make sure to save your receipt or reference number for verification."
            ],

            [
            'category' => 'Payments',
            'question' => 'How do I submit or verify my payment receipt?',
            'answer'   => "After paying for your request or application, submit your receipt or reference number based on how you applied:

            - **StudiOUS Portal Requests or Applications:** Upload your receipt or reference number directly within the portal before submitting your request.
            - **Walk-in Requests:** Submit your physical receipt directly to the OUS Office staff, or take a photo of it and send it to your Program In-Charge via Facebook Messenger."
            ],

            [
                'category' => 'Payments',
                'question' => 'What merchant name should I select on LandBank Link.BizPortal?',
                'answer'   => "When paying online via [LandBank's Link.BizPortal](https://www.lbp-eservices.com/egps/portal/index.jsp), search and select **PANGASINAN STATE UNIVERSITY - LINGAYEN** as the Merchant Name."
            ],
 
            // ================= COMPREHENSIVE EXAMINATION =================
            ['category' => 'Applications', 'question' => 'What are the requirements for the Comprehensive Examination application?',
             'answer' => 'You must have finished your academic requirements with no balance, and submit the Comprehensive Examination Application Form, the Comprehensive Examination Fee, and your Transfer Credential if it hasn\'t been processed yet.'],
 
            ['category' => 'Applications', 'question' => 'How much is the Comprehensive Examination fee?',
             'answer' => 'Php 300.00 for Master\'s students and Php 500.00 for Doctoral students.'],
 
            ['category' => 'Applications', 'question' => 'How long does the Comprehensive Examination application take to process?',
             'answer' => '3 to 5 working days once you\'ve submitted all requirements, plus about 50 minutes total for the in-person steps (filling out the form, paying the fee, etc.).'],
 
            ['category' => 'Applications', 'question' => 'What are the steps to apply for the Comprehensive Examination?',
             'answer' => "1) Fill out and submit the Application Form — your Program In-Charge releases the application and clearance form (about 25 minutes). 2) Pay the Comprehensive Examination Fee online via LandBank Link.BizPortal, or at the Cashier if you're a walk-in (about 20 minutes). 3) Submit all requirements and wait for your exam schedule (3 to 5 working days). 4) Accomplish the Client Feedback Form and send it to your program's person in-charge."],
 
            // ================= CERTIFICATION (CAV, COG, Official Certification, Certified True Copy (CTC), Authentication) =================
            ['category' => 'Documents', 'question' => 'What do I need to request a Certification, Certified True Copy (CTC), or Authentication?',
             'answer' => 'Just your Official Receipt after paying the applicable fee — this covers CAV, Certificate of Grades, Official Certification, Certified True Copy (CTC), and Authentication requests.'],
 
            ['category' => 'Documents', 'question' => 'How much does a Certification, Certified True Copy (CTC), or Authentication cost?',
             'answer' => 'Certification documents (CAV, Certificate of Grades, Official Certification) cost Php 60.00 per copy. Authentication/Certified True Copy (CTC) costs Php 30.00 per copy.'],
 
            ['category' => 'Documents', 'question' => 'How long does it take to get a Certification or Certified True Copy (CTC)?',
             'answer' => '5 to 10 working days, plus about 10 minutes for payment and claiming.'],
 
            ['category' => 'Documents', 'question' => 'What are the steps to request a Certification, Certified True Copy (CTC), or Authentication?',
             'answer' => "1) Pay the applicable fee online via LandBank Link.BizPortal, or at the Cashier if you're a walk-in (about 5 minutes). 2) Present your Official Receipt and claim your certification (5 to 10 working days). 3) Accomplish the Client Feedback Form."],
 
            // ================= TRANSFER CREDENTIAL =================
            ['category' => 'Documents', 'question' => 'What do I need to request a Transfer Credential?',
             'answer' => 'A Transfer Credential request and your Official Receipt.'],
 
            ['category' => 'Documents', 'question' => 'How much does a Transfer Credential cost?',
             'answer' => 'Around Php 310.00, covering the Documentary Stamp Tax and official Transcript of Records (OTR)-related charges. (Please verify this exact figure with the Cashier — it was the one amount that didn\'t convert cleanly from the source charter.)'],
 
            ['category' => 'Documents', 'question' => 'How long does a Transfer Credential take to process?',
             'answer' => 'The Transfer Credential itself is usually released within the day. If you also need your Official Transcript of Records processed alongside it, that portion takes 5 to 10 working days and only starts once the return slip from your receiving school is received.'],
 
            ['category' => 'Documents', 'question' => 'What are the steps to request a Transfer Credential?',
             'answer' => "1) Pay the Transfer Credential fee online or at the Cashier if you're a walk-in (about 5 minutes). 2) Present your Official Receipt to your Program In-Charge to start processing — the Transfer Credential is usually released the same day. 3) Accomplish the Client Feedback Form."],
 
            // ================= PROPOSAL & FINAL DEFENSE =================
            ['category' => 'Applications', 'question' => 'What are the requirements for Proposal Defense and Final Defense?',
             'answer' => 'For Proposal Defense, you must have passed your Title Defense. For Final Defense, you must have passed your Proposal Defense. Both also require clearance from the Statistics Center Office.'],
 
            ['category' => 'Applications', 'question' => 'How much does Proposal or Final Defense cost?',
             'answer' => "Tuition Fee: Php 5,575.00 for Master's, Php 6,675.00 for Doctoral (partial payment of 30-50% of total fees is accepted). Residency Fee: Php 700.00. Panel Fee: Php 4,000.00 (Master's) or Php 5,000.00 (Doctoral) for both Proposal and Final Defense. The Statistics Center also charges an Initial Reading fee of Php 500.00, plus a Final Reading fee that varies based on their assessment."],
 
            ['category' => 'Applications', 'question' => 'How long does the Proposal or Final Defense application take?',
             'answer' => '15 to 30 working days, plus about 1 hour for the in-person steps like enrollment, payment, and scheduling.'],
 
            ['category' => 'Applications', 'question' => 'What are the steps to apply for Proposal or Final Defense?',
             'answer' => "1) Enroll for Thesis/Dissertation Writing and get your Certificate of Enrollment (about 10 minutes). 2) Pay your Tuition Fee (new students) or Residency Fee (continuing students) (about 20 minutes). 3) Submit requirements to the Statistics Center Office and pay the Initial Clearance (for Proposal) or Final Clearance (for Final Defense) fee (15 to 30 working days). 4) Ask your Adviser and Critic Reader for their availability (about 20 minutes). 5) Inform the PSU-OUS Office of your agreed schedule and print hard copies of your manuscript — 6 copies for Master's, 7 copies for Doctoral (about 5 minutes). 6) Accomplish the Client Feedback Form."],
 
            // ================= TITLE DEFENSE =================
            ['category' => 'Applications', 'question' => 'What are the requirements for Title Defense?',
             'answer' => 'You must have passed the Comprehensive Examination.'],
 
            ['category' => 'Applications', 'question' => 'How much does Title Defense cost?',
             'answer' => "Tuition Fee: Php 5,575.00 for Master's, Php 6,675.00 for Doctoral. Partial payment of 30-50% of total fees is accepted."],
 
            ['category' => 'Applications', 'question' => 'How long does the Title Defense application take?',
             'answer' => "About 1 hour for the application steps themselves; your actual defense date depends on your Program In-Charge's available schedule."],
 
            ['category' => 'Applications', 'question' => 'What are the steps to apply for Title Defense?',
             'answer' => '1) Enroll for Thesis/Dissertation Writing and get your Certificate of Enrollment (about 10 minutes). 2) Pay your Tuition Fee (about 25 minutes). 3) Ask your Program In-Charge for an available Title Defense schedule (about 20 minutes). 4) Accomplish the Client Feedback Form.'],
 
            // ================= DIPLOMA =================
            ['category' => 'Graduation', 'question' => 'What do I need to claim my Diploma?',
             'answer' => 'Just the Official Receipt after paying the Diploma Fee. This service is for graduate students.'],
 
            ['category' => 'Graduation', 'question' => 'How much does the Diploma cost?',
             'answer' => 'Php 280.00.'],
 
            ['category' => 'Graduation', 'question' => 'How long does it take to get my Diploma?',
             'answer' => '15 to 30 working days depending on the availability of signatories, plus about 10 minutes for payment.'],
 
            ['category' => 'Graduation', 'question' => 'What are the steps to claim my Diploma?',
             'answer' => "1) Pay the Diploma Fee online or at the Cashier if you're a walk-in (about 5 minutes). 2) Present your Official Receipt to your Program In-Charge to start processing and release (15 to 30 working days, depending on signatory availability). 3) Accomplish the Client Feedback Form."],
 
            // ================= OFFICIAL TRANSCRIPT OF RECORDS (OTR) =================
            ['category' => 'Documents', 'question' => 'What do I need to request my Official Transcript of Records (OTR)?',
             'answer' => "A Transfer Credential and your Official Receipt. Graduate students must also submit bound copies of their manuscript before release: 6 copies of the Thesis Book for Master's, or 7 copies of the Dissertation Book for Doctoral."],
 
            ['category' => 'Documents', 'question' => 'How much does an Official Transcript of Records (OTR) cost?',
             'answer' => 'Php 230.00.'],
 
            ['category' => 'Documents', 'question' => 'How long does an Official Transcript of Records (OTR) take to process?',
             'answer' => '5 to 10 working days, plus about 5 minutes for payment.'],
 
            ['category' => 'Documents', 'question' => 'What are the steps to request my Official Transcript of Records (OTR)?',
             'answer' => "1) Pay the Official Transcript of Records (OTR) Fee online or at the Cashier if you're a walk-in (about 5 minutes). 2) Present your Official Receipt to your Program In-Charge to start processing and release (5 to 10 working days) — note that graduate students must submit their required bound manuscript copies (6 for Master's, 7 for Doctoral) before release. 3) Accomplish the Client Feedback Form."],
 
            // ============================================================
            //  NEW PROCESS — how these same services now work in StudiOUS.
            //  None of this exists in the Citizen's Charter, since the
            //  Charter describes the old walk-in process. Fees, requirements,
            //  and processing times above are still accurate policy — only
            //  the delivery channel and communication method have changed.
            // ============================================================
 
            // ---------------- Applications (general) ----------------
            ['category' => 'Using StudiOUS', 'question' => 'Do I still need to visit the office in person to submit an application?',
             'answer' => "No — with StudiOUS, you submit your application, upload your requirements, and pay online without visiting the Program In-Charge in person. You'll only need to be on campus for things that are inherently physical, like your actual defense (if you chose to conduct it on-site) or claiming a printed Diploma."],

            [
                'category' => 'Using StudiOUS',
                'question' => 'What are the available academic applications?',
                'answer'   => "You can submit various academic applications directly through your StudiOUS account, including:\n\n- Comprehensive Examination\n- Title Defense\n- Proposal Defense\n- Final Defense\n- Graduation Application"
            ],
 
            ['category' => 'Using StudiOUS', 'question' => 'How is applying through StudiOUS different from the old process?',
             'answer' => "The requirements, fees, and processing times from the Citizen's Charter still apply — that's policy and it hasn't changed. What's different is how you interact with the office: instead of visiting the Program In-Charge to release a paper form, you fill out the application online, upload requirements as files, and follow every status update yourself in your Application Tracker instead of waiting to be told in person."],
 
            ['category' => 'Using StudiOUS', 'question' => 'Is the Citizen\'s Charter still accurate now that StudiOUS exists?',
             'answer' => 'Yes — the fees, requirements, and processing times in the Citizen\'s Charter are still the official policy. StudiOUS only changes how you submit, pay, and track these services, not what\'s required or how much they cost.'],
 
            ['category' => 'Using StudiOUS', 'question' => 'What application statuses will I see, and what do they mean?',
             'answer' => "Submitted (received, not yet paid), Under Review (staff checking your requirements and receipt/reference), Returned (something needs fixing — check the remarks), Approved (requirements and payment cleared), Reschedule Requested (your proposed date wasn't approved), Scheduled (your date is confirmed), Completed (your defense/exam is done), or Rejected."],
 
            ['category' => 'Using StudiOUS', 'question' => 'Do I still need to bring my requirements in person?',
             'answer' => 'For most requirements, no — you upload them as files directly when you submit your application. You may still need to present an original document in person in specific cases where the office requires the physical copy, such as claiming a printed Diploma or OTR.'],
 
            // ---------------- Applications: the new scheduling flow ----------------
            ['category' => 'Applications', 'question' => 'How do I choose my defense schedule now?',
             'answer' => "You propose your own preferred date and time for your Title Defense, Proposal Defense, or Final Defense directly when you submit your application. Staff then reviews it and either confirms it or lets you know it needs to change."],
 
            ['category' => 'Applications', 'question' => 'What happens if my proposed defense date isn\'t approved?',
             'answer' => "Your application status changes to \"Reschedule Requested\" and you'll see the reason why (for example, panel availability). Go to your Application Details page and use \"Propose a New Date\" to submit another date for review — no need to go back to the office in person."],
 
            ['category' => 'Applications', 'question' => 'Who decides my final defense schedule, me or the staff?',
             'answer' => 'Both: you propose the date and time that works for you, and staff confirms it (adding the venue) or rejects it with a reason if there\'s a conflict, such as panel availability. You always get the final say by proposing again if your first date doesn\'t work out.'],
 
            ['category' => 'Applications', 'question' => 'How do I know if my application has moved forward?',
             'answer' => "You don't need to check in person or call the office. StudiOUS sends you an in-app notification and an email at every major step — when your application is approved (this includes your receipt/payment and reqiurements verfication), when your schedule is confirmed, or when it's returned for corrections."],
 
            // ---------------- Document Requests ----------------
            ['category' => 'Documents', 'question' => 'How do I request a document like my TOR or Certification now?',
             'answer' => 'Go to Document Requests → New Request in StudiOUS, choose the document type, state your purpose, and pay online — no need to visit the Program In-Charge just to start the request. The fees and processing times are the same as listed in the Citizen\'s Charter.'],
 
            ['category' => 'Documents', 'question' => 'Can I track my document request status online?',
             'answer' => 'Yes — every document request shows its live status (Submitted, Processing, Ready for Release, Completed) on your Document Requests page, so you always know exactly where it stands without needing to ask staff.'],
 
            ['category' => 'Documents', 'question' => 'How will I know when my document is ready for pickup?',
             'answer' => 'You\'ll get an in-app notification and an email the moment staff marks it "Ready for Release" — you don\'t need to keep checking back or calling the Registrar.'],
 
            [
                'category' => 'Documents',
                'question' => 'Do I still need to go to campus to get my document?',
                'answer'   => "Requesting and paying for documents can be done either online through the StudiOUS portal or in-person (walk-in) at the campus.\n\nHow you receive your document depends on how and where you requested it:\n\n- **Walk-in Requests:** If you requested and paid for your document directly on campus, you can claim the physical copy at the OUS Office. Please note that claiming is subject to document processing time, as some documents cannot be released immediately on the same day.\n- **Online Requests:** How you receive your document depends on the claiming option you selected in your online Document Request Form:\n  - **Pick-up / In-Person:** You or your authorized representative must visit the OUS Office on campus to claim the physical document.\n  - **Scanned Copy:** You can view and download the digital copy directly from your StudiOUS account once it is marked as ready to release.\n  - **Delivery via LBC:** The physical document will be shipped directly to your address via LBC. You can track the status through your StudiOUS account once it is ready for release.",
            ],
 
            // ---------------- Helpdesk ----------------
            ['category' => 'Support', 'question' => 'What is the Helpdesk for?',
             'answer' => 'The Helpdesk is where you go for anything that isn\'t answered by the Knowledge Base or by Leon — account issues, technical problems, a concern about a specific application, or anything you need a real staff member to look into personally.'],
 
            ['category' => 'Support', 'question' => 'Can guests without an account use the Helpdesk?',
             'answer' => 'Yes — guests can submit a Helpdesk ticket without logging in by providing their name and email. Staff replies are sent by email in that case, since guests don\'t have a portal account to check for in-app replies.'],
 
            ['category' => 'Support', 'question' => 'How do I track my Helpdesk ticket?',
             'answer' => 'If you\'re logged in, go to Helpdesk to see all your tickets and their status (Open, Processing, Resolved, Closed) along with the full conversation thread. Guests can track their ticket through the emails they receive.'],
 
            ['category' => 'Support', 'question' => 'What\'s the difference between asking Leon and opening a Helpdesk ticket?',
             'answer' => 'Ask Leon for general questions about requirements, fees, or how something works — he answers instantly using the Knowledge Base. Open a Helpdesk ticket when you need a staff member to personally look into something specific to your account, like a stuck application or a error loggin in to your student portal or Studious portal.'],
 
            // ---------------- Getting started / general ----------------
            ['category' => 'Using StudiOUS', 'question' => 'Can guests use StudiOUS without an account?',
             'answer' => 'Yes — guests can browse Announcements, the Academic Calendar, the Knowledge Base, and the Contact Directory, and can submit a Helpdesk ticket, all without logging in. Submitting applications or document requests requires logging in as a Student.'],
 
            ['category' => 'Using StudiOUS', 'question' => 'Where can I ask questions about requirements or fees now?',
             'answer' => 'You can ask Leon (AI Chatbot) directly, browse the Knowledge Base for detailed articles organized by topic, or open a Helpdesk ticket if you need help with something specific to your own account.'],
 
            [
                'category' => 'Using StudiOUS',
                'question' => 'Do I need to create a new account for StudiOUS?',
                'answer' => "Yes, you are encouraged to create an account to fully access university student services, especially if you're an enrolled student. You can use either your personal email or institutional email."
            ],
        ];

        foreach ($rows as $row) {
            // Concatenate Category, Question, and Answer for complete vector coverage
            $textToEmbed = "Category: {$row['category']} | Question: {$row['question']} | Answer: {$row['answer']}";

            // Clean markdown tokens so vector space focuses on pure key concepts like 'fee', '₱230.00', etc.
            $cleanEmbedText = preg_replace('/[\*\#\_]/', '', $textToEmbed);

            // Generate vector embedding
            $embedding = $reflector->invoke($controller, $cleanEmbedText);

            if (!$embedding) {
                Log::warning("Seeder failed to generate embedding for: {$row['question']}");
            }

            ChatbotKnowledge::updateOrCreate(
                ['question' => $row['question']],
                [
                    'category'  => $row['category'],
                    'answer'    => $row['answer'],
                    'embedding' => $embedding ? json_encode($embedding) : null,
                ]
            );

            // 300ms pause to prevent hitting API rate limits during batch seeding
            usleep(300000); 
        }
    }
}