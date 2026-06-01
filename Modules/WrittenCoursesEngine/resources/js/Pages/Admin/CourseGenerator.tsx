import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function CourseGenerator() {
    const [blueprint, setBlueprint] = useState('');
    const [isGenerating, setIsGenerating] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');
    const [errorMessage, setErrorMessage] = useState('');

    const handleGenerate = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsGenerating(true);
        setSuccessMessage('');
        setErrorMessage('');

        try {
            // Since we are using standard inertia routing in a real app,
            // we'll simulate the axios call for demonstration
            const response = await window.axios.post('/api/admin/written-courses/generate', {
                blueprint
            });

            if (response.data.message) {
                setSuccessMessage(`Course Generated Successfully! Course Slug: ${response.data.course_slug}`);
                setBlueprint('');
            }
        } catch (error: any) {
            setErrorMessage(error.response?.data?.message || 'An error occurred during generation.');
        } finally {
            setIsGenerating(false);
        }
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{__('general.ai_course_generator')}</h2>}
        >
            <Head title={__('general.course_generator')} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                        <div className="p-8">
                            <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">{__('general.create_new_course')}</h3>
                            <p className="text-gray-500 dark:text-gray-400 text-sm mb-6">{__('general.enter_a_detailed_blueprint_for_the_course_the_ai_will_generate_the_entire_markdown_directory_structure_metadata_and_lesson_content')}</p>

                            {successMessage && (
                                <div className="mb-4 p-4 rounded-md bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300">
                                    {successMessage}
                                </div>
                            )}

                            {errorMessage && (
                                <div className="mb-4 p-4 rounded-md bg-red-50 border border-red-200 text-red-700 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300">
                                    {errorMessage}
                                </div>
                            )}

                            <form onSubmit={handleGenerate}>
                                <div className="mb-6">
                                    <label htmlFor="blueprint" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{__('general.course_blueprint_prompt')}</label>
                                    <textarea
                                        id="blueprint"
                                        rows={8}
                                        className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        placeholder={__('general.e_g_create_a_5_module_course_on_advanced_next_js_app_router_focusing_on_server_actions_parallel_routing_and_caching_strategies')}
                                        value={blueprint}
                                        onChange={(e) => setBlueprint(e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="flex justify-end">
                                    <button
                                        type="submit"
                                        disabled={isGenerating || blueprint.length < 10}
                                        className="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    >
                                        {isGenerating ? (
                                            <>
                                                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>{__('general.generating_architecture')}</>
                                        ) : (
                                            'Generate Markdown Architecture'
                                        )}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
