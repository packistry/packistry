import * as React from 'react'
import { createFileRoute, Outlet, redirect } from '@tanstack/react-router'
import { Sidebar } from '@/components/sidebar'
import { ThemeToggle } from '@/components/theme-toggle'
import { AuthDropdownMenu } from '@/components/dropdown-menu/auth-dropdown-menu'

export const Route = createFileRoute('/_auth')({
    beforeLoad: ({ context, location }) => {
        if (!context.auth.isAuthenticated) {
            throw redirect({
                to: '/login',
                search: {
                    redirect: location.href,
                },
            })
        }
    },
    component: AuthLayout,
})

function AuthLayout() {
    return (
        <>
            <Sidebar />
            <main className="w-full">
                <header className="flex items-center justify-end h-16 px-4 bg-background border-b">
                    <div className="flex items-center space-x-4">
                        <ThemeToggle />
                        <AuthDropdownMenu />
                    </div>
                </header>
                <div className="container mx-auto px-4 py-8 space-y-6">
                    <Outlet />
                </div>
            </main>
        </>
    )
}
