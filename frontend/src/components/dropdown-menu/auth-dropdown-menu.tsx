import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { UserAvatar } from '@/components/avatar/user-avatar'
import { LogOut } from 'lucide-react'
import * as React from 'react'
import { useAuth } from '@/auth'
import { Link, useNavigate } from '@tanstack/react-router'

export function AuthDropdownMenu() {
    const { logout, user: maybeUser } = useAuth()
    const navigate = useNavigate()
    const user = maybeUser!

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    className="relative h-8 w-8 rounded-full"
                >
                    <UserAvatar user={user} />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem className="font-medium">{user.name}</DropdownMenuItem>
                <Link to="/personal-tokens">
                    <DropdownMenuItem className="font-medium">Personal Tokens</DropdownMenuItem>
                </Link>
                <DropdownMenuItem
                    onClick={() =>
                        logout().then(() => {
                            // @todo ?
                            setTimeout(() => {
                                navigate({
                                    to: '/',
                                })
                            }, 0)
                        })
                    }
                >
                    <LogOut className="mr-2 h-4 w-4" />
                    <span>Log out</span>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    )
}
