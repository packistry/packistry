import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { CodeIcon, DatabaseIcon, HomeIcon, KeyIcon, PackageIcon, UsersIcon } from 'lucide-react'
import { Link } from '@tanstack/react-router'
import React, { JSXElementConstructor } from 'react'
import {
    DASHBOARD,
    DEPLOY_TOKEN_READ,
    PACKAGE_READ,
    Permission,
    REPOSITORY_READ,
    SOURCE_READ,
    USER_READ,
} from '@/permission'
import { useAuth } from '@/auth'

type NavItem = {
    name: string
    href: string
    icon: JSXElementConstructor<{ className: string }>
    permission?: Permission
}

const navItems: NavItem[] = [
    { name: 'Dashboard', href: '/', icon: HomeIcon, permission: DASHBOARD },
    { name: 'Repositories', href: '/repositories', icon: DatabaseIcon, permission: REPOSITORY_READ },
    { name: 'Packages', href: '/packages', icon: PackageIcon, permission: PACKAGE_READ },
    { name: 'Sources', href: '/sources', icon: CodeIcon, permission: SOURCE_READ },
    { name: 'Users', href: '/users', icon: UsersIcon, permission: USER_READ },
    { name: 'Deploy Tokens', href: '/deploy-tokens', icon: KeyIcon, permission: DEPLOY_TOKEN_READ },
]

export function Sidebar() {
    const { can } = useAuth()
    return (
        <div className="flex h-full w-64 flex-col bg-background border-r">
            <div className="flex h-16 items-center justify-between px-4 border-b">
                <h1 className="text-xl font-bold">Packistry</h1>
            </div>
            <nav className="flex-1 space-y-1 p-4">
                {navItems
                    .filter(({ permission }) => {
                        if (typeof permission === 'undefined') {
                            return true
                        }

                        return can(permission)
                    })
                    .map((item) => (
                        <Link
                            tabIndex={-1}
                            key={item.name}
                            to={item.href}
                        >
                            {({ isActive }) => {
                                return (
                                    <Button
                                        variant="ghost"
                                        className={cn('w-full justify-start', isActive ? 'bg-muted' : '')}
                                    >
                                        <item.icon className="mr-2 h-5 w-5" />
                                        {item.name}
                                    </Button>
                                )
                            }}
                        </Link>
                    ))}
            </nav>
        </div>
    )
}
