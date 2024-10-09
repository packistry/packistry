import * as React from 'react'
import { createFileRoute, redirect, useNavigate } from '@tanstack/react-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { z } from 'zod'
import { FormInput } from '@/components/form/elements/FormInput'
import { useLogin } from '@/api/hooks'
import { useForm } from '@/hooks/useForm'
import { Form } from '@/components/ui/form'

const fallback = '/' as const

export const Route = createFileRoute('/login')({
    validateSearch: z.object({
        redirect: z.string().optional().catch(''),
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
            // @todo ?
            setTimeout(() => {
                navigate({
                    to: search.redirect || fallback,
                })
            }, 0)
        },
    })

    return (
        <div className="flex items-center justify-center  w-full">
            <Card>
                <CardHeader>
                    <CardTitle>Login</CardTitle>
                    <CardDescription>Enter your credentials to access your account</CardDescription>
                </CardHeader>

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
                        </CardContent>
                        <CardFooter className="flex flex-col">
                            <Button
                                className="w-full"
                                type="submit"
                                loading={isPending}
                            >
                                Login
                            </Button>
                        </CardFooter>
                    </form>
                </Form>
            </Card>
        </div>
    )
}
