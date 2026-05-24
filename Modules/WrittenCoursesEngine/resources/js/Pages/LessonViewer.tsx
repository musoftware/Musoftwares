import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import MarkdownRenderer from '../Components/MarkdownRenderer';

interface LessonViewerProps {
    course: any;
    currentLesson: any;
    lessonContent: string;
}

export default function LessonViewer({ course, currentLesson, lessonContent }: LessonViewerProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center space-x-4">
                    <Link href="/written-courses" className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clipRule="evenodd" />
                        </svg>
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight truncate">
                        {course.title}
                    </h2>
                </div>
            }
        >
            <Head title={`${currentLesson?.title || 'Lesson'} - ${course.title}`} />

            <div className="py-8">
                <div className="max-w-8xl mx-auto sm:px-6 lg:px-8 flex flex-col lg:flex-row gap-8">
                    
                    {/* Sidebar / Table of Contents */}
                    <div className="w-full lg:w-80 flex-shrink-0">
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg sticky top-8">
                            <div className="p-4 border-b border-gray-100 dark:border-gray-700">
                                <h3 className="font-bold text-gray-900 dark:text-gray-100">Course Content</h3>
                            </div>
                            <div className="p-2 max-h-[calc(100vh-12rem)] overflow-y-auto">
                                {course.modules?.map((module: any, mIdx: number) => (
                                    <div key={module.slug} className="mb-4 last:mb-0">
                                        <h4 className="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Module {mIdx + 1}: {module.title}
                                        </h4>
                                        <ul className="mt-1 space-y-1">
                                            {module.lessons?.map((lesson: any, lIdx: number) => {
                                                const isActive = currentLesson?.slug === lesson.slug;
                                                return (
                                                    <li key={lesson.slug}>
                                                        <Link
                                                            href={`/written-courses/${course.slug}/modules/${module.slug}/lessons/${lesson.slug}`}
                                                            className={`group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                                                                isActive 
                                                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' 
                                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'
                                                            }`}
                                                        >
                                                            <span className={`w-6 h-6 mr-3 flex items-center justify-center rounded-full border text-xs ${
                                                                isActive 
                                                                    ? 'border-indigo-600 bg-indigo-600 text-white dark:border-indigo-500 dark:bg-indigo-500' 
                                                                    : 'border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400 group-hover:border-gray-400'
                                                            }`}>
                                                                {lIdx + 1}
                                                            </span>
                                                            <span className="truncate">{lesson.title}</span>
                                                        </Link>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Main Content Area */}
                    <div className="flex-1 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden min-h-[500px]">
                        <div className="p-8 lg:p-12">
                            {lessonContent ? (
                                <MarkdownRenderer content={lessonContent} />
                            ) : (
                                <div className="flex justify-center items-center h-64 text-gray-500">
                                    Loading content...
                                </div>
                            )}
                            
                            <div className="mt-16 pt-8 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                {/* Navigation buttons would go here (Previous / Next) */}
                                <button className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Previous Lesson
                                </button>
                                
                                <button className="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none transition-colors flex items-center">
                                    Mark as Complete & Next
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fillRule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clipRule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
