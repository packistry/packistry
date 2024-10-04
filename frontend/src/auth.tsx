import * as React from 'react'
import { useCallback, useContext, useState } from 'react'
import { logout as apiLogout, User, user } from '@/api'
import { Permission } from '@/permission'

export interface AuthContext {
    isAuthenticated: boolean
    login: (user: User) => Promise<void>
    logout: () => Promise<void>
    user: User | null
    can: (permission: Permission) => boolean
}

const AuthContext = React.createContext<AuthContext | null>(null)

const key = 'packistry.auth.user'

function getStoredUser() {
    try {
        const json = localStorage.getItem(key)

        if (json === null) {
            return json
        }

        return user.parse(JSON.parse(json))
    } catch {
        setStoredUser(null)
        window.location.reload()
    }

    return null
}

export function setStoredUser(user: User | null) {
    if (user) {
        localStorage.setItem(key, JSON.stringify(user))
        return
    }

    localStorage.removeItem(key)
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser] = useState<User | null>(getStoredUser())
    const isAuthenticated = !!user

    const logout = useCallback(async () => {
        await apiLogout()
        setStoredUser(null)
        setUser(null)
    }, [])

    const login = useCallback(async (user: User) => {
        setStoredUser(user)
        setUser(user)
    }, [])

    const can = useCallback(
        (permission: Permission) => {
            if (user === null) {
                return false
            }

            return user.permissions.includes(permission)
        },
        [user]
    )

    return <AuthContext.Provider value={{ isAuthenticated, user, login, logout, can }}>{children}</AuthContext.Provider>
}

export function useAuth() {
    const context = useContext(AuthContext)
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider')
    }
    return context
}
