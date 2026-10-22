<?php

namespace Modules\SoftwareStore\Database\Seeders;

/**
 * The long part of each landing page: full details and common questions.
 * Kept apart from the seeder so the seeder stays readable. Only facts the programs really have.
 */
class ProductExtras
{
    /** @return array{details: array, faq: array} */
    public static function for(string $slug, string $lang): array
    {
        return (self::all()[$slug][$lang] ?? []) + ['overview' => self::overview()[$slug][$lang] ?? []];
    }

    /** A short "read this first" block: who it is for, what you need, what happens after paying. */
    private static function overview(): array
    {
        $after = [
            'ar' => 'بعد الدفع بيتعمل حسابك والترخيص تلقائي، ولو فتحت صفحة الشراء من نافذة البرنامج بيتفعل جهازك فورا. وبتوصلك رسالة واتساب جاهزة برقم طلبك.',
            'en' => 'After payment your account and license are created automatically, and if you opened the buy page from the program window your device is activated right away. You also get a ready WhatsApp message with your order number.',
        ];
        $afterManual = [
            'ar' => 'بعد الدفع بتبعتلنا رقم طلبك على الواتساب (الزرار بيظهر بعد الدفع)، ونبدأ التجهيز ونبعتلك التفاصيل.',
            'en' => 'After payment you send us your order number on WhatsApp (the button appears after payment), and we start the setup and send you the details.',
        ];

        return [
            'audfinder' => [
                'ar' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                    ['title' => 'مناسب لمين؟', 'body' => 'المسوقين وأصحاب الشغل اللي عندهم قوائم بروفايلات فيسبوك ومحتاجين أرقام التواصل.'],
                    ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 (64 بت) أو ماك. مفيش حاجة تانية.'],
                    ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => $after['ar']],
                ]],
                'en' => ['title' => 'Understand everything in one minute', 'items' => [
                    ['title' => 'Who is it for?', 'body' => 'Marketers and business owners who have lists of Facebook profiles and need contact numbers.'],
                    ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 (64-bit) computer, or a Mac. Nothing else.'],
                    ['title' => 'What happens after payment?', 'body' => $after['en']],
                ]],
            ],
            'email-sender-pro' => [
                'ar' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                    ['title' => 'مناسب لمين؟', 'body' => 'اللي بيبعت حملات إيميل لعملاء وافقوا يستلموا رسايله، ومحتاج يدير أكتر من حساب إرسال.'],
                    ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 أو ماك، وحسابات SMTP بتاعتك، ودومين تبعت منه.'],
                    ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => $after['ar']],
                ]],
                'en' => ['title' => 'Understand everything in one minute', 'items' => [
                    ['title' => 'Who is it for?', 'body' => 'People who send email campaigns to customers who agreed to receive them, and need to manage more than one sending account.'],
                    ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 computer or a Mac, your own SMTP accounts and a domain to send from.'],
                    ['title' => 'What happens after payment?', 'body' => $after['en']],
                ]],
            ],
            'whatsapp-bulk-sender' => [
                'ar' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                    ['title' => 'مناسب لمين؟', 'body' => 'اللي بيرسل عروض أو إشعارات لعملاء وافقوا على استلامها ومحتاج يربط أكتر من رقم واتساب.'],
                    ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز 10 أو 11 أو ماك، وهاتف عليه واتساب لكل حساب عشان تمسح كود QR.'],
                    ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => $after['ar']],
                ]],
                'en' => ['title' => 'Understand everything in one minute', 'items' => [
                    ['title' => 'Who is it for?', 'body' => 'People who send offers or notices to customers who agreed to receive them and need to link more than one WhatsApp number.'],
                    ['title' => 'What do you need?', 'body' => 'A Windows 10 or 11 computer or a Mac, and a phone with WhatsApp for each account to scan the QR code.'],
                    ['title' => 'What happens after payment?', 'body' => $after['en']],
                ]],
            ],
            'marketing-suite' => [
                'ar' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                    ['title' => 'مناسبة لمين؟', 'body' => 'المسوقين وفرق المبيعات والشركات الصغيرة اللي بتشتغل على أكتر من منصة وعايزة أدوات في مكان واحد.'],
                    ['title' => 'محتاج إيه؟', 'body' => 'جهاز ويندوز للبرامج، ومتصفح كروم للإضافات، وحسابات المنصات اللي هتشتغل عليها.'],
                    ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => $afterManual['ar']],
                ]],
                'en' => ['title' => 'Understand everything in one minute', 'items' => [
                    ['title' => 'Who is it for?', 'body' => 'Marketers, sales teams and small businesses that work on more than one platform and want their tools in one place.'],
                    ['title' => 'What do you need?', 'body' => 'A Windows computer for the apps, the Chrome browser for the extensions, and the accounts of the platforms you will work on.'],
                    ['title' => 'What happens after payment?', 'body' => $afterManual['en']],
                ]],
            ],
            'whatsapp-official' => [
                'ar' => ['title' => 'اعرف كل حاجة في دقيقة', 'items' => [
                    ['title' => 'مناسبة لمين؟', 'body' => 'الشركات اللي عايزة تبعت رسايل تسويقية لعملائها على واتساب بالطريقة الرسمية من ميتا.'],
                    ['title' => 'محتاج إيه؟', 'body' => 'نشاط تجاري ومستندات الشركة لتوثيق حساب Meta Business، ورقم واتساب مخصص للأعمال.'],
                    ['title' => 'بعد الدفع إيه اللي بيحصل؟', 'body' => $afterManual['ar']],
                ]],
                'en' => ['title' => 'Understand everything in one minute', 'items' => [
                    ['title' => 'Who is it for?', 'body' => 'Companies that want to send marketing messages to their customers on WhatsApp the official Meta way.'],
                    ['title' => 'What do you need?', 'body' => 'A business and its company documents to verify the Meta Business account, and a WhatsApp number for business use.'],
                    ['title' => 'What happens after payment?', 'body' => $afterManual['en']],
                ]],
            ],
        ];
    }

    private static function all(): array
    {
        return [
            'audfinder' => [
                'ar' => [
                    'details' => [
                        'title' => 'تفاصيل البرنامج',
                        'paragraphs' => [
                            'أود فايندر برنامج لويندوز وماك بيحوّل قوائم بروفايلات فيسبوك لأرقام تليفون وبيانات جمهور. بترفع ملف إكسل أو CSV أو نص فيه روابط أو أرقام البروفايلات، والبرنامج يطابقها مع قاعدة بيانات محلية مشفرة على جهازك ويطلعلك الأرقام.',
                            'كل المعالجة بتحصل على جهازك. مفيش رفع للقوائم على أي سيرفر، فبياناتك وعملاءك المحتملين مش بيخرجوا برا.',
                        ],
                        'list_title' => 'اللي هتحصل عليه',
                        'list' => [
                            'استيراد من إكسل و CSV وملفات نصية',
                            'بحث مباشر عن بروفايل واحد بدون ملف',
                            'تحليل ديموغرافي للجمهور حسب الدولة',
                            'تصدير النتيجة بصيغة جاهزة لحملاتك، ومنها تصدير لبرنامج واتساب بلك سيندر',
                            'قاعدة بيانات مشفرة AES-256 على جهازك',
                            'تحديثات تلقائية بدون ما تنزل البرنامج من جديد',
                        ],
                    ],
                    'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                        ['q' => 'البرنامج بيشتغل على أنهي جهاز؟', 'a' => 'بيشتغل على ويندوز 10 أو 11 (64 بت) وعلى ماك.'],
                        ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد. بعد الدفع بيتفعل الجهاز تلقائي لو فتحت صفحة الشراء من نافذة البرنامج، أو تربط حسابك بالإيميل.'],
                        ['q' => 'بياناتي بتتبعت لسيرفر؟', 'a' => 'لا. المطابقة بتحصل على جهازك على قاعدة بيانات محلية مشفرة.'],
                        ['q' => 'التحديثات بتيجي إزاي؟', 'a' => 'البرنامج بيتحقق من وجود نسخة جديدة وينزلها لوحده ويطلب منك إعادة التشغيل.'],
                        ['q' => 'لو احتجت مساعدة؟', 'a' => 'كلمنا على الواتساب برقم طلبك وهنساعدك.'],
                    ]],
                ],
                'en' => [
                    'details' => [
                        'title' => 'Program details',
                        'paragraphs' => [
                            'AudFinder is a program for Windows and Mac that turns lists of Facebook profiles into phone numbers and audience data. You load an Excel, CSV or text file of profile links or IDs, and the program matches them against a local encrypted database on your computer.',
                            'All processing happens on your computer. Your lists are never uploaded to a server, so your leads stay with you.',
                        ],
                        'list_title' => 'What you get',
                        'list' => [
                            'Import from Excel, CSV and text files',
                            'Direct search for a single profile with no file',
                            'Audience analysis by country',
                            'Export in a format ready for your campaigns, including export to WhatsApp Bulk Sender',
                            'AES-256 encrypted database on your computer',
                            'Automatic updates, no need to download again',
                        ],
                    ],
                    'faq' => ['title' => 'Common questions', 'items' => [
                        ['q' => 'Which computers does it run on?', 'a' => 'It runs on Windows 10 or 11 (64-bit) and on Mac.'],
                        ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device. After payment the device is activated automatically if you opened the buy page from the program window, or you can link your account by email.'],
                        ['q' => 'Is my data sent to a server?', 'a' => 'No. Matching runs on your computer against a local encrypted database.'],
                        ['q' => 'How do updates arrive?', 'a' => 'The program checks for a new version, downloads it by itself and asks you to restart.'],
                        ['q' => 'What if I need help?', 'a' => 'Message us on WhatsApp with your order number and we will help.'],
                    ]],
                ],
            ],
            'email-sender-pro' => [
                'ar' => [
                    'details' => [
                        'title' => 'تفاصيل البرنامج',
                        'paragraphs' => [
                            'إيميل سيندر برو برنامج لويندوز وماك للحملات البريدية الجماعية. بتضيف حسابات SMTP كتير، والبرنامج يوزع الإرسال عليها ويسخّن الحسابات بالتدريج، وتفحص إعدادات الدومين قبل ما تبعت.',
                            'بتجهز الحملة برسالة HTML ومتغيرات للاسم والشركة، وتختبر صيغتين للعنوان (A/B)، وتتابع التقارير وسجل الأنشطة وانت شغال.',
                        ],
                        'list_title' => 'اللي هتحصل عليه',
                        'list' => [
                            'عدة حسابات SMTP مع تدوير وجداول تسخين',
                            'فحص SPF و DKIM و DMARC للدومين',
                            'اختبار A/B للعنوان وSpintax لتنويع الرسالة',
                            'قوائم الجهات وقائمة حظر لمن طلب الإيقاف',
                            'استيراد جهات الاتصال من CSV وإكسل',
                            'تقارير التسليم وسجل الأنشطة',
                            'تحديثات تلقائية',
                        ],
                    ],
                    'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                        ['q' => 'البرنامج بيشتغل على أنهي جهاز؟', 'a' => 'بيشتغل على ويندوز 10 أو 11 (64 بت) وعلى ماك.'],
                        ['q' => 'هل لازم عندي حسابات SMTP؟', 'a' => 'أيوه، بتضيف حسابات الإرسال بتاعتك (جيميل أو أوتلوك أو SES أو SendGrid أو أي SMTP).'],
                        ['q' => 'هل البرنامج يضمن الوصول للإنبوكس؟', 'a' => 'لا أحد يقدر يضمن ده. البرنامج بيساعدك بالتسخين وفحص الدومين والتوزيع، لكن السمعة بتعتمد على قوائمك ومحتواك.'],
                        ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
                        ['q' => 'لو احتجت مساعدة؟', 'a' => 'كلمنا على الواتساب برقم طلبك.'],
                    ]],
                ],
                'en' => [
                    'details' => [
                        'title' => 'Program details',
                        'paragraphs' => [
                            'Email Sender Pro is a program for Windows and Mac for bulk email campaigns. You add many SMTP accounts, and the program spreads sending across them, warms accounts up slowly and lets you check your domain setup before you send.',
                            'You prepare the campaign with an HTML message and name and company variables, test two subject lines (A/B), and follow reports and the activity log while you work.',
                        ],
                        'list_title' => 'What you get',
                        'list' => [
                            'Several SMTP accounts with rotation and warm-up schedules',
                            'SPF, DKIM and DMARC checks for your domain',
                            'A/B subject test and spintax to vary the message',
                            'Contact lists and a suppression list for people who opted out',
                            'Contact import from CSV and Excel',
                            'Delivery reports and an activity log',
                            'Automatic updates',
                        ],
                    ],
                    'faq' => ['title' => 'Common questions', 'items' => [
                        ['q' => 'Which computers does it run on?', 'a' => 'It runs on Windows 10 or 11 (64-bit) and on Mac.'],
                        ['q' => 'Do I need my own SMTP accounts?', 'a' => 'Yes. You add your own sending accounts (Gmail, Outlook, SES, SendGrid or any SMTP).'],
                        ['q' => 'Does it guarantee inbox delivery?', 'a' => 'Nobody can guarantee that. The program helps with warm-up, domain checks and spreading the load, but reputation depends on your lists and content.'],
                        ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
                        ['q' => 'What if I need help?', 'a' => 'Message us on WhatsApp with your order number.'],
                    ]],
                ],
            ],
            'whatsapp-bulk-sender' => [
                'ar' => [
                    'details' => [
                        'title' => 'تفاصيل البرنامج',
                        'paragraphs' => [
                            'واتساب بلك سيندر برنامج لويندوز وماك للإرسال الجماعي على واتساب من أكتر من حساب. بتربط كل حساب بكود QR، وتجهز الرسالة بنص وصور وفيديو ومستندات، والبرنامج يبعت بفواصل زمنية عشوائية بين الرسايل.',
                            'البرنامج فيه قوائم جهات اتصال وقوالب رسائل وتقارير للحملات، وتقدر تفلتر الأرقام الموجودة فعلا على واتساب قبل الإرسال.',
                        ],
                        'list_title' => 'اللي هتحصل عليه',
                        'list' => [
                            'حسابات متعددة بتسجيل دخول QR',
                            'محرر رسالة بمتغيرات للاسم والرقم وSpintax',
                            'صور وفيديو ومستندات مع الرسالة',
                            'فواصل إرسال عشوائية وراحة دورية بين الدفعات',
                            'فلترة الأرقام الموجودة على واتساب',
                            'قوائم جهات اتصال وقوالب وجدولة',
                            'تذييل لإلغاء الاشتراك وقائمة عدم الإرسال',
                            'استخراج جهات الاتصال والجروبات والإرسال حسب الجروب',
                            'سجل الحملات والتقارير وتحديثات تلقائية',
                        ],
                    ],
                    'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                        ['q' => 'هل ممكن رقمي يتحظر؟', 'a' => 'أي إرسال جماعي على واتساب فيه خطر تقييد الحساب. البرنامج بيقلل الخطر بالفواصل العشوائية وتنويع الرسالة وفلترة الأرقام، لكن مفيش ضمان. التزم بسياسات واتساب وابعت لناس وافقوا على استلام رسايلك.'],
                        ['q' => 'البرنامج بيشتغل على أنهي جهاز؟', 'a' => 'بيشتغل على ويندوز 10 أو 11 (64 بت) وعلى ماك.'],
                        ['q' => 'كام حساب أقدر أربط؟', 'a' => 'عدد القنوات بيتحدد من الترخيص بتاعك.'],
                        ['q' => 'الترخيص مرتبط بإيه؟', 'a' => 'ترخيص سنوي على جهاز واحد، ويتفعل تلقائي بعد الدفع.'],
                        ['q' => 'لو احتجت مساعدة؟', 'a' => 'كلمنا على الواتساب برقم طلبك.'],
                    ]],
                ],
                'en' => [
                    'details' => [
                        'title' => 'Program details',
                        'paragraphs' => [
                            'WhatsApp Bulk Sender is a program for Windows and Mac for sending WhatsApp messages in bulk from several accounts. You link each account with a QR code, prepare the message with text, photos, video and documents, and the program sends with random delays between messages.',
                            'It has contact lists, message templates and campaign reports, and it can filter the numbers that really have WhatsApp before sending.',
                        ],
                        'list_title' => 'What you get',
                        'list' => [
                            'Several accounts with QR login',
                            'Message editor with name and number variables and spintax',
                            'Photos, video and documents with the message',
                            'Random send delays and rest periods between batches',
                            'Filter of numbers that have WhatsApp',
                            'Contact lists, templates and scheduling',
                            'Opt-out footer and a do-not-contact list',
                            'Extract contacts and groups, and send by group',
                            'Campaign history, reports and automatic updates',
                        ],
                    ],
                    'faq' => ['title' => 'Common questions', 'items' => [
                        ['q' => 'Can my number get banned?', 'a' => 'Any bulk sending on WhatsApp risks account restrictions. The program lowers the risk with random delays, message variation and number filtering, but there is no guarantee. Follow WhatsApp policies and message people who agreed to receive your messages.'],
                        ['q' => 'Which computers does it run on?', 'a' => 'It runs on Windows 10 or 11 (64-bit) and on Mac.'],
                        ['q' => 'How many accounts can I link?', 'a' => 'The number of channels is set by your license.'],
                        ['q' => 'What is the license tied to?', 'a' => 'A yearly license for one device, activated automatically after payment.'],
                        ['q' => 'What if I need help?', 'a' => 'Message us on WhatsApp with your order number.'],
                    ]],
                ],
            ],
            'marketing-suite' => [
                'ar' => [
                    'details' => [
                        'title' => 'تفاصيل الحزمة',
                        'paragraphs' => [
                            'الحزمة عضوية سنوية واحدة بتديك برامج سطح مكتب وإضافات متصفح لشغل التسويق: الرسائل الجماعية، وجمع العملاء المحتملين، وتنضيف البيانات، وحملات SMS وواتساب، وروابط قصيرة بتتبع، وإدارة المبيعات والموظفين.',
                            'كل الباقات فيها نفس الأدوات. الفرق بين الباقات في نقاط الجمهور (كل نقطة عميل محتمل جديد) وعدد البوتات الشاملة.',
                        ],
                        'list_title' => 'الأدوات اللي في العضوية',
                        'list' => [
                            'تسعة برامج سطح مكتب: واتساب، تيليجرام، إنستجرام، مركز اتصال، تنضيف بيانات، خرائط جوجل، أدلة الأعمال، إعلانات مبوبة، فيسبوك إنبوكس',
                            'ست إضافات متصفح لجمع العملاء المحتملين من التعليقات والمنشورات والجروبات وإرسال رسائل واتساب',
                            'حملات SMS بروابط تتبع',
                            'حملات واتساب مع بوت',
                            'اختصار الروابط مع التتبع',
                            'إدارة المبيعات والموظفين ونظام دعم',
                        ],
                    ],
                    'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                        ['q' => 'إيه الفرق بين الباقات؟', 'a' => 'نفس الأدوات في الكل. الفرق في نقاط الجمهور (80 ألف و160 ألف و240 ألف و400 ألف) وعدد البوتات الشاملة (1 و3 و5 و10).'],
                        ['q' => 'يعني إيه نقطة جمهور؟', 'a' => 'كل نقطة تساوي عميل محتمل جديد بتجمعه بالأدوات.'],
                        ['q' => 'إزاي بستلم العضوية بعد الدفع؟', 'a' => 'بعد الدفع بتبعتلنا رقم طلبك على الواتساب، ونجهز لك العضوية ونبعتلك بيانات الدخول.'],
                        ['q' => 'العضوية مدتها كام؟', 'a' => 'سنة كاملة من تاريخ التفعيل.'],
                        ['q' => 'فيه ضمان إن الحسابات مش هتتقيد؟', 'a' => 'لا. أدوات الرسائل الجماعية فيها خطر تقييد الحسابات على أي منصة، فالتزم بسياسات كل منصة.'],
                        ['q' => 'الدعم الفني إزاي؟', 'a' => 'من خلال نظام التذاكر، وكمان تقدر تكلمنا على الواتساب في موضوع طلبك.'],
                    ]],
                ],
                'en' => [
                    'details' => [
                        'title' => 'Suite details',
                        'paragraphs' => [
                            'The suite is one yearly membership that gives you desktop apps and browser extensions for marketing work: bulk messaging, lead collection, data cleaning, SMS and WhatsApp campaigns, tracked short links, and sales and staff management.',
                            'Every plan has the same tools. Plans differ in audience points (each point is one new lead) and the number of global bots.',
                        ],
                        'list_title' => 'Tools in the membership',
                        'list' => [
                            'Nine desktop apps: WhatsApp, Telegram, Instagram, call center, data cleaning, Google Maps, business directories, classified ads, Facebook inbox',
                            'Six browser extensions to collect leads from comments, posts and groups and to send WhatsApp messages',
                            'SMS campaigns with tracking links',
                            'WhatsApp campaigns with a bot',
                            'Link shortener with tracking',
                            'Sales and staff management and a support system',
                        ],
                    ],
                    'faq' => ['title' => 'Common questions', 'items' => [
                        ['q' => 'What is the difference between plans?', 'a' => 'The tools are the same. The difference is audience points (80K, 160K, 240K and 400K) and global bots (1, 3, 5 and 10).'],
                        ['q' => 'What is an audience point?', 'a' => 'One point equals one new lead you collect with the tools.'],
                        ['q' => 'How do I get the membership after paying?', 'a' => 'After payment you send us your order number on WhatsApp, and we prepare your membership and send your login details.'],
                        ['q' => 'How long is the membership?', 'a' => 'One full year from the activation date.'],
                        ['q' => 'Is there a guarantee that accounts will not be restricted?', 'a' => 'No. Bulk messaging tools carry a risk of account restrictions on any platform, so follow each platform rules.'],
                        ['q' => 'How does support work?', 'a' => 'Through a ticket system, and you can also message us on WhatsApp about your order.'],
                    ]],
                ],
            ],
            'whatsapp-official' => [
                'ar' => [
                    'details' => [
                        'title' => 'تفاصيل الخدمة',
                        'paragraphs' => [
                            'الخدمة بتجهز لك واتساب الأعمال الرسمي عن طريق Cloud API من ميتا: إنشاء وتوثيق حساب Meta Business، وتفعيل رقم الواتساب، وكتابة قوالب الرسائل واعتمادها، وإطلاق حملاتك مع تقارير التسليم والقراءة.',
                            'بتدفع رسوم التأسيس مرة واحدة، وبعدها بتشحن رصيد وبيتخصم على حسب الرسايل اللي بتبعتها. مفيش اشتراك شهري إجباري.',
                        ],
                        'list_title' => 'اللي هتحصل عليه',
                        'list' => [
                            'إنشاء وتوثيق Meta Business Suite',
                            'تفعيل رقم الواتساب على Cloud API واعتماد الاسم',
                            'كتابة قوالب الرسائل واعتمادها مع أزرار الإجراء',
                            'صندوق وارد موحد وتصنيفات للعملاء',
                            'إطلاق أول حملة بإشراف',
                            'تقارير التسليم والقراءة والردود',
                            'خدمات إضافية عند الطلب: شات بوت، وكيل ذكاء اصطناعي، تصميم موقع، سحب أرقام',
                        ],
                    ],
                    'faq' => ['title' => 'أسئلة شائعة', 'items' => [
                        ['q' => 'ليه الإرسال الرسمي أحسن؟', 'a' => 'لأنه عن طريق واجهة ميتا الرسمية بقوالب معتمدة، فمش بيعتمد على برامج غير رسمية ممكن حسابها يتقيد.'],
                        ['q' => 'التأسيس والتوثيق بياخد وقت قد إيه؟', 'a' => 'بيعتمد على مراجعة ميتا للمستندات. ممكن ياخد من أيام لأسابيع، ومفيش مدة مضمونة.'],
                        ['q' => 'الرسالة بتتحاسب إزاي؟', 'a' => 'بتتحاسب على كل رسالة تسويقية بتبعتها من رصيدك. الردود اللي بتيجي خلال 24 ساعة من رد العميل على حسب قواعد ميتا.'],
                        ['q' => 'محتاج أدي كلمات سر؟', 'a' => 'لا. الشغل بيتم بصلاحيات محدودة على حساب Meta Business بتاعك من غير ما تشارك كلمات السر.'],
                        ['q' => 'إزاي أطلب؟', 'a' => 'اختار الباقة وادفع، وبعدها ابعتلنا رقم طلبك على الواتساب ونبدأ.'],
                    ]],
                ],
                'en' => [
                    'details' => [
                        'title' => 'Service details',
                        'paragraphs' => [
                            'We set up the official WhatsApp Business through the Meta Cloud API for you: creating and verifying your Meta Business account, activating your WhatsApp number, writing and getting message templates approved, and launching your campaigns with delivery and read reports.',
                            'You pay a one-time setup fee, then top up a balance that is used as you send messages. There is no mandatory monthly subscription.',
                        ],
                        'list_title' => 'What you get',
                        'list' => [
                            'Meta Business Suite creation and verification',
                            'Number activation on the Cloud API and display name approval',
                            'Message templates written and approved, with action buttons',
                            'A shared inbox and customer tags',
                            'A supervised first campaign',
                            'Delivery, read and reply reports',
                            'Extra services on request: chatbot, AI agent, website design, number extraction',
                        ],
                    ],
                    'faq' => ['title' => 'Common questions', 'items' => [
                        ['q' => 'Why is official sending better?', 'a' => 'Because it uses the official Meta interface with approved templates, so it does not depend on unofficial tools whose accounts can be restricted.'],
                        ['q' => 'How long do setup and verification take?', 'a' => 'It depends on Meta reviewing your documents. It can take from days to weeks, and no time is guaranteed.'],
                        ['q' => 'How is a message charged?', 'a' => 'Each marketing message you send is charged from your balance. Replies that come within 24 hours of the customer reply follow Meta rules.'],
                        ['q' => 'Do I have to give passwords?', 'a' => 'No. The work is done with limited permissions on your own Meta Business account, without sharing passwords.'],
                        ['q' => 'How do I order?', 'a' => 'Choose a plan and pay, then send us your order number on WhatsApp and we start.'],
                    ]],
                ],
            ],
        ];
    }
}
