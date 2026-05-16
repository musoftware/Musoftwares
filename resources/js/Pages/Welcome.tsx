import { ThemeProvider } from '@/components/shared/ThemeProvider';
import { Button } from '@/components/ui/button';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({
    auth,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    return (
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background p-4">
                <div className="max-w-2xl space-y-8 text-center">
                    <h1 className="text-4xl font-extrabold tracking-tight lg:text-5xl">
                        Welcome to the SaaS Platform
                    </h1>
                    <p className="text-xl text-muted-foreground">
                        A modern, enterprise-grade architecture.
                    </p>
                    <div className="flex justify-center gap-4">
                        {auth.user ? (
                            <Button asChild size="lg">
                                <Link href={route('dashboard')}>
                                    Go to Dashboard
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild size="lg">
                                    <Link href={route('login')}>Log in</Link>
                                </Button>
                                <Button asChild variant="outline" size="lg">
                                    <Link href={route('register')}>
                                        Register
                                    </Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </ThemeProvider>
    );
}
