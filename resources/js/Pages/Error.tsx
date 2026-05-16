import { Head, Link } from '@inertiajs/react';

export default function Error({ status }: { status: number }) {
    const title =
        {
            503: '503: Service Unavailable',
            500: '500: Server Error',
            404: '404: Page Not Found',
            403: '403: Forbidden',
        }[status] || 'Error';

    const description =
        {
            503: 'Sorry, we are doing some maintenance. Please check back soon.',
            500: 'Whoops, something went wrong on our servers.',
            404: 'Sorry, the page you are looking for could not be found.',
            403: 'Sorry, you are forbidden from accessing this page.',
        }[status] || 'An unexpected error occurred.';

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100 px-4 py-12 sm:px-6 lg:px-8">
            <Head title={title} />
            <div className="mx-auto w-full max-w-md text-center">
                <h1 className="text-9xl font-extrabold text-indigo-600">
                    {status}
                </h1>
                <h2 className="mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    {title.split(': ')[1]}
                </h2>
                <p className="mt-4 text-lg text-gray-500">{description}</p>
                <div className="mt-8">
                    <Link
                        href="/"
                        className="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Go back home
                    </Link>
                </div>
            </div>
        </div>
    );
}
