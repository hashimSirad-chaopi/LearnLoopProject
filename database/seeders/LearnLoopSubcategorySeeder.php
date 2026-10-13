<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LearnLoopSubcategorySeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'Programming & Software Development' => [
                ['Python Programming', 'Python syntax, variables, loops, functions, data structures, and scripting.'],
                ['Java Programming', 'Java syntax, object-oriented programming, classes, methods, and application fundamentals.'],
                ['C and C++ Programming', 'Programming fundamentals, control flow, functions, and memory concepts.'],
                ['C# and .NET', 'C# fundamentals, object-oriented programming, and .NET applications.'],
                ['Algorithms & Data Structures', 'Problem-solving, searching, sorting, arrays, lists, stacks, and queues.'],
                ['Git & Version Control', 'Repositories, commits, branches, merges, and collaborative development.'],
            ],

            'Web Development' => [
                ['HTML & Web Structure', 'HTML elements, links, forms, semantic structure, and accessible page markup.'],
                ['CSS & Responsive Design', 'Selectors, layouts, Flexbox, Grid, and responsive web pages.'],
                ['JavaScript & DOM', 'Variables, functions, events, DOM manipulation, and interactive websites.'],
                ['React Development', 'Components, props, state, hooks, and reusable frontend interfaces.'],
                ['PHP & Laravel', 'PHP fundamentals, routes, controllers, Blade templates, and database-backed applications.'],
                ['APIs & Web Integration', 'HTTP basics, JSON, REST APIs, and connecting web applications to services.'],
            ],

            'Mobile App Development' => [
                ['Dart Fundamentals', 'Variables, functions, collections, classes, and object-oriented Dart.'],
                ['Flutter UI Development', 'Widgets, layouts, navigation, forms, and reusable mobile interfaces.'],
                ['Mobile App State Management', 'Managing application state and sharing data between screens.'],
                ['Mobile Storage & APIs', 'Local storage, HTTP requests, and connecting applications to backends.'],
                ['Mobile Testing & Debugging', 'Testing app behavior, diagnosing problems, and fixing defects.'],
            ],

            'Data, Databases & Analytics' => [
                ['SQL Fundamentals', 'SELECT queries, filtering, sorting, joins, grouping, and aggregation.'],
                ['Database Design', 'Tables, keys, relationships, normalization, and relational data modeling.'],
                ['MySQL & SQLite', 'Creating tables, querying records, and managing relational databases.'],
                ['Spreadsheets & Excel', 'Formulas, functions, data cleaning, tables, and spreadsheet analysis.'],
                ['Data Visualization', 'Charts, dashboards, and presenting data clearly.'],
            ],

            'Languages & Communication' => [
                ['English Conversation', 'Everyday vocabulary, listening, speaking, and conversational fluency.'],
                ['English Grammar & Writing', 'Sentence structure, grammar, paragraph writing, and editing.'],
                ['Filipino Language', 'Filipino vocabulary, grammar, conversation, and writing.'],
                ['Japanese Language', 'Japanese writing systems, vocabulary, grammar, and basic conversation.'],
                ['Public Speaking', 'Presentation structure, speech delivery, and audience communication.'],
            ],

            'Design, UI/UX & Multimedia' => [
                ['Graphic Design', 'Composition, typography, color, visual hierarchy, and digital design.'],
                ['UI Design', 'Interface layouts, components, consistency, and visual design.'],
                ['UX Research & Wireframing', 'User needs, user flows, wireframes, and usability fundamentals.'],
                ['Digital Illustration', 'Digital drawing, shapes, shading, color, and illustration tools.'],
                ['Photo & Video Editing', 'Editing workflows, image adjustments, transitions, and visual storytelling.'],
            ],

            'Academic Subjects & Tutoring' => [
                ['Mathematics', 'Arithmetic, algebra, geometry, and other mathematical topics.'],
                ['Biology', 'Cells, genetics, ecology, and human biology.'],
                ['Physics', 'Motion, forces, energy, electricity, and waves.'],
                ['Chemistry', 'Atoms, chemical bonding, reactions, and calculations.'],
                ['History & Social Studies', 'Historical events, civics, society, and research skills.'],
                ['Accounting', 'Basic accounting principles, financial records, and statements.'],
            ],

            'Business, Entrepreneurship & Office Tools' => [
                ['Entrepreneurship', 'Business ideas, planning, basic business models, and launching projects.'],
                ['Bookkeeping', 'Recording transactions, organizing accounts, and maintaining financial records.'],
                ['Microsoft Excel', 'Formulas, functions, lookups, data tables, and reports.'],
                ['PowerPoint & Presentations', 'Slide design, presentation structure, and communicating information.'],
                ['Project Management', 'Task planning, timelines, priorities, and collaboration.'],
            ],

            'Arts, Crafts & DIY' => [
                ['Drawing & Painting', 'Sketching, shading, composition, color, and painting techniques.'],
                ['Sewing & Textile Crafts', 'Basic stitches, patterns, fabric handling, and simple repairs.'],
                ['Knitting & Crochet', 'Stitches, patterns, and making practical or decorative items.'],
                ['Handmade Crafts', 'Paper crafts, decorations, and other creative projects.'],
            ],

            'Cooking & Baking' => [
                ['Cooking Fundamentals', 'Food preparation, basic cooking methods, and kitchen organization.'],
                ['Baking Fundamentals', 'Measurements, mixing, doughs, and common baking methods.'],
                ['Filipino Cuisine', 'Traditional dishes, ingredients, and cooking techniques.'],
                ['Meal Planning', 'Recipe organization, meal preparation, and planning balanced meals.'],
            ],

            'Music & Performing Arts' => [
                ['Guitar', 'Basic chords, strumming, rhythm, and playing simple songs.'],
                ['Piano & Keyboard', 'Notes, scales, chords, rhythm, and accompaniment.'],
                ['Singing & Vocal Skills', 'Pitch, diction, breathing, and vocal practice.'],
                ['Music Theory', 'Rhythm, intervals, scales, chords, and harmony.'],
                ['Acting & Performance', 'Expression, character work, performance techniques, and stage skills.'],
            ],

            'Fitness & Wellness' => [
                ['Sports Skills', 'Fundamental techniques, rules, strategy, and sports practice.'],
                ['Mobility & Flexibility', 'Basic mobility exercises, movement skills, and safe stretching.'],
                ['Dance', 'Rhythm, coordination, basic steps, and dance practice.'],
                ['General Wellness', 'Everyday wellness habits, recovery, and healthy routines.'],
            ],

            'Test Preparation & Study Skills' => [
                ['Exam Preparation', 'Review schedules, practice questions, and exam preparation strategies.'],
                ['Note-taking', 'Organizing notes, identifying key ideas, and summarizing information.'],
                ['Study Planning', 'Time management, revision schedules, and organizing learning goals.'],
                ['Research & Academic Writing', 'Research methods, outlining, citations, and academic writing.'],
            ],

            'AI, Machine Learning & Automation' => [
                ['AI Fundamentals', 'Basic AI concepts, common uses, limitations, and responsible use.'],
                ['Prompt Design', 'Writing clear instructions and refining prompts for generative AI tools.'],
                ['Machine Learning Basics', 'Datasets, training concepts, model evaluation, and common terminology.'],
                ['Task Automation', 'Automating repetitive digital tasks and organizing workflows.'],
            ],

            'Digital Marketing & Content Creation' => [
                ['Social Media Content', 'Content planning, post formats, and audience engagement.'],
                ['Search Engine Optimization', 'Keywords, page structure, and improving search visibility.'],
                ['Copywriting', 'Writing clear and persuasive messages for different audiences.'],
                ['Video Content Creation', 'Scripting, recording, editing, and publishing video content.'],
                ['Photography', 'Composition, lighting, framing, and visual storytelling.'],
            ],
        ];

        DB::transaction(function () use ($skills) {
            foreach ($skills as $categoryName => $subcategories) {
                $category = Category::where('name', $categoryName)->firstOrFail();

                foreach ($subcategories as [$name, $description]) {
                    Subcategory::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'name' => $name,
                        ],
                        [
                            'description' => $description,
                        ]
                    );
                }
            }
        });
    }
}