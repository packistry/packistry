import * as React from 'react'
import { useCallback, useContext, useEffect, useState } from 'react'
import { logout as apiLogout, User } from '@/api'
import { Permission } from '@/permission'
import { useQueryClient } from '@tanstack/react-query'

export interface AuthContext {
    isAuthenticated: boolean
    login: (user: User) => void
    logout: () => Promise<void>
    user: User | null
    can: (permission: Permission) => boolean
}

const AuthContext = React.createContext<AuthContext | null>(null)

export function AuthProvider({ children, user }: { children: React.ReactNode; user: User | null }) {
    const [authUser, setUser] = useState<User | null>(user)
    const isAuthenticated = !!authUser
    const queryClient = useQueryClient()

    useEffect(() => {
        setUser(user)
    }, [user])

    const logout = useCallback(async () => {
        await apiLogout()
        queryClient.clear()
        setUser(null)
    }, [])

    const login = useCallback((user: User) => {
        setUser(user)
    }, [])

    const can = useCallback(
        (permission: Permission) => {
            if (authUser === null) {
                return false
            }

            return authUser.permissions.includes(permission)
        },
        [authUser]
    )

    return (
        <AuthContext.Provider value={{ isAuthenticated, user: authUser, login, logout, can }}>
            {children}
        </AuthContext.Provider>
    )
}

export function useAuth() {
    const context = useContext(AuthContext)
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider')
    }
    return context
}
