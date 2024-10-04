import React from 'react'
import ReactDOM from 'react-dom/client'
import { createRouter, RouterProvider } from '@tanstack/react-router'
import { routeTree } from './routeTree.gen'
import './index.css'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { AuthProvider, useAuth } from '@/auth'

const router = createRouter({
    routeTree,
    defaultPreload: 'intent',
    context: {
        auth: undefined!,
    },
})

export type Router = typeof router

declare module '@tanstack/react-router' {
    interface Register {
        router: Router
    }
}

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 60000,
        },
    },
})

function InnerApp() {
    const auth = useAuth()
    return (
        <RouterProvider
            router={router}
            context={{ auth }}
        />
    )
}

function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <AuthProvider>
                <InnerApp />
            </AuthProvider>
        </QueryClientProvider>
    )
}

const rootElement = document.getElementById('app')!

if (!rootElement.innerHTML) {
    const root = ReactDOM.createRoot(rootElement)
    root.render(<App />)
}
