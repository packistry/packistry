import * as React from 'react'
import { createRootRouteWithContext, Outlet } from '@tanstack/react-router'
import { TanStackRouterDevtools } from '@tanstack/router-devtools'
import { ThemeProvider } from '@/components/theme-provider'
import { AuthContext } from '@/auth'
import { Toaster } from '@/components/ui/sonner'

interface RouterContext {
    auth: AuthContext
}

export const Route = createRootRouteWithContext<RouterContext>()({
    component: RootComponent,
})

function RootComponent() {
    return (
        <div className="flex h-screen bg-background text-foreground">
            <ThemeProvider
                defaultTheme="dark"
                storageKey="vite-ui-theme"
            >
                <Outlet />
                <Toaster />
            </ThemeProvider>
            {import.meta.env.DEV && <TanStackRouterDevtools position="bottom-right" />}
        </div>
    )
}
