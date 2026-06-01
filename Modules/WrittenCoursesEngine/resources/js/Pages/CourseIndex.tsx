import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface Course {
    slug: string;
    title: string;
    description: string;
    difficulty: string;
    tags: string[];
    estimated_time?: number;
    modules: any[];
}

interface CourseIndexProps {
    courses: Course[];
}

export default function CourseIndex({ courses }: CourseIndexProps) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{__('general.written_courses')}</h2>}
        >
            <Head title="Courses" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {courses && courses.map((course) => (
                            <div key={course.slug} className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow border border-gray-100 dark:border-gray-700">
                                <div className="p-6">
                                    <div className="flex justify-between items-start mb-4">
                                        <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 line-clamp-2">
                                            {course.title}
                                        </h3>
                                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {course.difficulty}
                                        </span>
                                    </div>
                                    <p className="text-gray-500 dark:text-gray-400 text-sm line-clamp-3 mb-6 min-h-[4rem]">
                                        {course.description}
                                    </p>
                                    
                                    <div className="flex flex-wrap gap-2 mb-6">
                                        {course.tags && course.tags.map(tag => (
                                            <span key={tag} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                #{tag}
                                            </span>
                                        ))}
                                    </div>

                                    <div className="mt-auto">
                                        {course.modules && course.modules.length > 0 && course.modules[0].lessons && course.modules[0].lessons.length > 0 ? (
                                            <Link
                                                href={`/written-courses/${course.slug}/modules/${course.modules[0].slug}/lessons/${course.modules[0].lessons[0].slug}`}
                                                className="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 transition-colors"
                                            >{__('general.start_course')}</Link>
                                        ) : (
                                            <button disabled className="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-500 bg-gray-200 dark:bg-gray-700 dark:text-gray-400 cursor-not-allowed">{__('general.coming_soon')}</button>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                        {(!courses || courses.length === 0) && (
                            <div className="col-span-full py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path vectorEffect="non-scaling-stroke" strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">{__('general.no_courses')}</h3>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{__('general.get_started_by_creating_a_new_course_via_the_ai_generator')}</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
