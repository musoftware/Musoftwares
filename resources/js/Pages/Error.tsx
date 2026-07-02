import { Head, Link } from '@inertiajs/react';
import { __ } from '@/lib/i18n';
import { Button } from '@/components/ui/button';
import { motion } from 'framer-motion';

export default function Error({ status, message }: { status: number; message?: string }) {
    const title: Record<number, string> = {
        503: '503: Service Unavailable',
        500: '500: Server Error',
        429: '429: Too Many Requests',
        404: '404: Page Not Found',
        403: '403: Forbidden',
    };
    const displayTitle = title[status] || 'Error';

    const description: Record<number, string> = {
        503: 'Sorry, we are doing some maintenance. Please check back soon.',
        500: 'Whoops, something went wrong on our servers. We are looking into it.',
        429: 'You have made too many requests. Please wait a moment and try again.',
        404: 'Sorry, the page you are looking for could not be found.',
        403: 'Sorry, you are forbidden from accessing this page.',
    };
    const displayDescription = message || description[status] || 'An unexpected error occurred.';

    return (
        <div className="flex min-h-screen items-center justify-center bg-zinc-50 dark:bg-zinc-950 px-4 py-12 sm:px-6 lg:px-8 selection:bg-zinc-900 selection:text-white dark:selection:bg-zinc-100 dark:selection:text-zinc-900">
            <Head title={displayTitle} />
            
            <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, ease: "easeOut" }}
                className="mx-auto w-full max-w-2xl text-center space-y-8"
            >
                <div className="space-y-4">
                    <h1 className="text-7xl sm:text-9xl font-extrabold tracking-tighter text-zinc-900 dark:text-zinc-100">
                        {status}
                    </h1>
                    <div className="space-y-2">
                        <h2 className="text-2xl sm:text-3xl font-semibold tracking-tight text-zinc-800 dark:text-zinc-200">
                            {displayTitle.split(': ')[1] || 'Error'}
                        </h2>
                        <p className="text-sm sm:text-base text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">
                            {displayDescription}
                        </p>
                    </div>
                </div>

                <motion.div 
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.2, duration: 0.5 }}
                    className="flex justify-center"
                >
                    <Link href="/">
                        <Button className="h-11 px-8 rounded-full bg-zinc-900 text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200 transition-all font-medium">
                            {__('general.go_back_home') || 'Go Back Home'}
                        </Button>
                    </Link>
                </motion.div>
            </motion.div>
        </div>
    );
}
