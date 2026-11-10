<?php

/**
 * Once the written topics in config/blog_topics.php are used, the daily article picks its own topic from these
 * angles, one after another. Each angle is the kind of search that brings a buyer, not just a reader.
 * 'brief' tells the writer what to cover; 'link' is the page the article must link to when no product fits.
 */
return [
    ['key' => 'business-software', 'link' => '/ai',
        'brief' => 'Software for ONE specific kind of business (choose one that is not in the recent titles: a type of shop, clinic, workshop, school, factory, office, delivery or service business). What it really needs, the mistakes owners make when buying, and building custom versus buying ready-made. Link to the matching product page if one fits, otherwise to /ai where the owner describes the business in a chat and gets a price.'],
    ['key' => 'excel-to-program', 'link' => '/ai',
        'brief' => 'A business that runs on Excel sheets (choose a specific case: stock, salaries, installments, orders, debts, production costs...). The signs the sheet is no longer enough, and how its calculations become a real program or app with users, permissions and reports. Link to /ai: the owner can upload the Excel file in the chat, and it reads the calculations before giving a price.'],
    ['key' => 'android-app', 'link' => '/ai',
        'brief' => 'An Android app for a small business (choose a specific use). Explain the three honest options: a simple app that keeps data on the phone, a full app with a server and an admin panel, and a polished app with a richer design; which one fits which business and why. Link to /ai.'],
    ['key' => 'company-website', 'link' => '/ai',
        'brief' => 'A website for a specific kind of company. What pages it needs, why the design should be agreed before programming, what makes it appear in Google search, and what to ask before paying anyone. Link to /ai or to a matching product page.'],
    ['key' => 'daily-problem', 'link' => '/ai',
        'brief' => 'One daily problem a business owner has that software solves (choose one: lost stock, unpaid customer debts, missed appointments, staff attendance, delivery orders, branch reports...). Practical steps to fix it, with or without software, and when a program is worth it. Link to the matching product page or to /ai.'],
    ['key' => 'ai-builder', 'link' => '/ai',
        'brief' => 'Getting software built through an AI chat (choose one narrow question a buyer asks about it). Be honest about what it does well and where a person is still needed. Link to /ai.'],
];
