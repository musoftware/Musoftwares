import React, { useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import mermaid from 'mermaid';

interface MarkdownRendererProps {
    content: string;
}

export default function MarkdownRenderer({ content }: MarkdownRendererProps) {
    useEffect(() => {
        mermaid.initialize({
            startOnLoad: true,
            theme: 'default',
            securityLevel: 'loose',
        });
        // Render any mermaid charts found in the DOM
        mermaid.contentLoaded();
    }, [content]);

    return (
        <div className="prose prose-slate dark:prose-invert max-w-none w-full">
            <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={{
                    code({ node, inline, className, children, ...props }: any) {
                        const match = /language-(\w+)/.exec(className || '');
                        const language = match ? match[1] : '';

                        if (!inline && language === 'mermaid') {
                            return (
                                <div className="mermaid flex justify-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg overflow-x-auto">
                                    {String(children).replace(/\n$/, '')}
                                </div>
                            );
                        }

                        return !inline ? (
                            <div className="relative group">
                                <button
                                    onClick={() => navigator.clipboard.writeText(String(children))}
                                    className="absolute right-2 top-2 p-1.5 bg-gray-700 hover:bg-gray-600 text-white rounded text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    Copy
                                </button>
                                <pre className="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm font-mono mt-4 mb-4">
                                    <code className={className} {...props}>
                                        {children}
                                    </code>
                                </pre>
                            </div>
                        ) : (
                            <code className="bg-gray-100 dark:bg-gray-800 text-pink-600 dark:text-pink-400 px-1.5 py-0.5 rounded text-sm font-mono" {...props}>
                                {children}
                            </code>
                        );
                    },
                    // You can add more custom components for custom tags like ::: quiz
                    div({ className, children, ...props }: any) {
                        if (className === 'quiz') {
                            return (
                                <div className="p-6 border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-950 rounded-lg my-6 shadow-sm">
                                    <h4 className="flex items-center gap-2 text-blue-800 dark:text-blue-300 font-bold mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                        Knowledge Check
                                    </h4>
                                    <div className="text-gray-700 dark:text-gray-300">
                                        {children}
                                    </div>
                                </div>
                            );
                        }
                        return <div className={className} {...props}>{children}</div>;
                    }
                }}
            >
                {content}
            </ReactMarkdown>
        </div>
    );
}
