import * as React from 'react'
import { createFileRoute, redirect, useNavigate } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { z } from 'zod'
import { FormInput } from '@/components/form/elements/form-input'
import { useLogin, usePublicAuthenticationSources } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Form } from '@/components/ui/form'
import { Separator } from '@/components/ui/separator'
import { CircleX } from 'lucide-react'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'

const fallback = '/' as const

export const Route = createFileRoute('/login')({
    validateSearch: z.object({
        redirect: z.string().optional().catch(''),
        error: z.string().optional().catch(''),
    }),
    beforeLoad: ({ context, search }) => {
        if (context.auth.isAuthenticated) {
            throw redirect({ to: search.redirect || fallback })
        }
    },
    component: LoginComponent,
})

export default function LoginComponent() {
    const mutation = useLogin()
    const navigate = useNavigate()
    const search = Route.useSearch()

    const { form, onSubmit, isPending } = useForm({
        mutation,
        defaultValues: {
            email: '',
            password: '',
        },
        onSuccess() {
            navigate({
                to: search.redirect || fallback,
            })
        },
    })

    return (
        <div className="flex items-center justify-center  w-full">
            <Card>
                <CardHeader>
                    <CardTitle>Sign in to your account</CardTitle>
                    <CardDescription>Enter your credentials to access your account</CardDescription>
                </CardHeader>

                {typeof search.error !== 'undefined' && (
                    <Alert variant="darkDestructive">
                        <CircleX className="h-4 w-4" />
                        <AlertTitle>Unable to login</AlertTitle>
                        <AlertDescription>{search.error}</AlertDescription>
                    </Alert>
                )}

                <Form {...form}>
                    <form onSubmit={onSubmit}>
                        <CardContent className="space-y-4">
                            <FormInput
                                label="Email"
                                name="email"
                                control={form.control}
                            />
                            <FormInput
                                label="Password"
                                name="password"
                                type="password"
                                control={form.control}
                            />
                            <Button
                                className="w-full"
                                type="submit"
                                loading={isPending}
                            >
                                Sign in
                            </Button>
                            <AuthSourceSignInOptions />
                        </CardContent>
                    </form>
                </Form>
            </Card>
        </div>
    )
}

function AuthSourceSignInOptions() {
    const sources = usePublicAuthenticationSources()

    if (!sources.data?.length) {
        return <></>
    }

    return (
        <div className="w-full flex gap-4 flex-col">
            <div className="flex items-center gap-4 w-full">
                <Separator className="flex-1" />
                <span className="text-muted-foreground">or</span>
                <Separator className="flex-1" />
            </div>
            {sources.data?.map((authSource) => {
                return (
                    <a
                        key={authSource.id}
                        href={authSource.redirectUrl}
                    >
                        <Button
                            className="w-full"
                            type="button"
                        >
                            {authSource.iconUrl && (
                                <img
                                    className="max-h-6 max-w-6 mr-2"
                                    src={authSource.iconUrl}
                                    alt={`${authSource.name} icon`}
                                />
                            )}
                            Sign in with {authSource.name}
                        </Button>
                    </a>
                )
            })}
        </div>
    )
}
