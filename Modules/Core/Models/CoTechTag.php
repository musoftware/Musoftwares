<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoTechTag extends Model
{
    use HasFactory;

    protected $table = 'co_tech_tags';

    protected $fillable = [
        'tag_name',
        'tag_description',
    ];

    public function coWorkers()
    {
        return $this->belongsToMany(CoWorker::class, 'co_tech_tags_workers', 'co_tech_tag_id', 'co_worker_id');
    }

    public static function seed()
    {
        if (self::count() > 0) {
            return;
        }

        $programmingTechnologies = [
            // Software Development
            'PHP' => 'PHP programming language',
            'Laravel' => 'Laravel PHP framework',
            'Python' => 'Python programming language',
            'Django' => 'Django Python web framework',
            'Flask' => 'Flask Python web framework',
            'Java' => 'Java programming language',
            'Spring Boot' => 'Spring Boot Java framework',
            'C#' => 'C# programming language',
            '.NET' => '.NET framework',
            'ASP.NET' => 'ASP.NET web framework',
            'Ruby' => 'Ruby programming language',
            'Ruby on Rails' => 'Ruby on Rails web framework',
            'Go' => 'Go programming language',
            'Rust' => 'Rust programming language',
            'Node.js' => 'Node.js JavaScript runtime',
            'TypeScript' => 'TypeScript programming language',

            // Web Design & Development
            'HTML/CSS' => 'HTML and CSS web technologies',
            'JavaScript' => 'JavaScript programming language',
            'Vue.js' => 'Vue.js JavaScript framework',
            'React' => 'React JavaScript library',
            'Angular' => 'Angular JavaScript framework',
            'Bootstrap' => 'Bootstrap CSS framework',
            'Tailwind CSS' => 'Tailwind CSS utility framework',
            'SASS/SCSS' => 'SASS/SCSS CSS preprocessor',
            'WordPress' => 'WordPress CMS',

            // E-commerce Solutions
            'Shopify' => 'Shopify e-commerce platform',
            'Magento' => 'Magento e-commerce platform',
            'WooCommerce' => 'WooCommerce e-commerce plugin for WordPress',

            // Mobile App Development
            'Swift' => 'Swift programming language',
            'Kotlin' => 'Kotlin programming language',
            'Dart' => 'Dart programming language',
            'Flutter' => 'Flutter mobile framework',
            'React Native' => 'React Native mobile framework',

            // Website Maintenance & Support
            'Git' => 'Git version control',
            'Docker' => 'Docker containerization',
            'Kubernetes' => 'Kubernetes container orchestration',
            'Linux' => 'Linux operating system',
            'AWS' => 'Amazon Web Services',
            'Azure' => 'Microsoft Azure cloud platform',
            'MySQL' => 'MySQL database',
            'PostgreSQL' => 'PostgreSQL database',
            'MongoDB' => 'MongoDB NoSQL database',
            'Redis' => 'Redis in-memory database',

            // Digital Marketing & SEO
            'SEO' => 'Search Engine Optimization',
            'Google Analytics' => 'Google Analytics web analytics service',
            'Google Ads' => 'Google Ads online advertising platform',
            'Facebook Ads' => 'Facebook Ads advertising platform',
            'Content Marketing' => 'Content Marketing strategy',
            'Social Media Marketing' => 'Social Media Marketing strategy',

            // AI & Machine Learning
            'Machine Learning' => 'Machine Learning algorithms and models',
            'Deep Learning' => 'Deep Learning neural networks',
            'TensorFlow' => 'TensorFlow machine learning framework',
            'PyTorch' => 'PyTorch machine learning framework',
            'OpenAI API' => 'OpenAI API for AI applications',
            'ChatGPT' => 'ChatGPT conversational AI',
            'Natural Language Processing' => 'Natural Language Processing (NLP)',
            'Computer Vision' => 'Computer Vision image processing',
            'Data Science' => 'Data Science analytics and insights',
            'Scikit-learn' => 'Scikit-learn machine learning library',
            'Pandas' => 'Pandas data analysis library',
            'NumPy' => 'NumPy numerical computing library',
        ];

        foreach ($programmingTechnologies as $tagName => $description) {
            self::firstOrCreate(
                ['tag_name' => $tagName],
                ['tag_description' => $description]
            );
        }
    }
}
