/* eslint-disable @typescript-eslint/no-explicit-any */
import { z, ZodObject } from 'zod'
import { useForm as rcUseForm, UseFormReturn } from 'react-hook-form'

import { zodResolver } from '@hookform/resolvers/zod'
import { UseMutationResult } from '@tanstack/react-query'
import { AxiosError } from 'axios'

type AnyMutationResult = UseMutationResult<any, any, any, any>

type AddEmptyString<T> = {
    [K in keyof T]: T[K] | ''
}

export function useForm<Mutation extends AnyMutationResult>({
    mutation,
    schema,
    onSuccess,
    defaultValues,
}: {
    mutation: Mutation
    onSuccess?: (result: Awaited<ReturnType<Mutation['mutateAsync']>>) => any
    schema?: ZodObject<any>
    defaultValues: AddEmptyString<Parameters<Mutation['mutateAsync']>[0]>
}) {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const zodSchema = schema ? schema : z.any()

    const form = rcUseForm<z.infer<typeof zodSchema>>({
        resolver: schema ? zodResolver(schema) : undefined,
        defaultValues,
    })

    function onSubmit(values: z.infer<typeof zodSchema>) {
        mutation.mutateAsync(values).then(onSuccess).catch(addValidationOnError(form))
    }

    return {
        form,
        isPending: mutation.isPending,
        onSubmit: form.handleSubmit(onSubmit),
    }
}

export type ValidationError<T> = AxiosError<{
    errors: Record<keyof T, string>
}>

export function addValidationToForm(form: UseFormReturn<any>, error: ValidationError<any>) {
    const { errors } = error.response?.data || {}

    if (!errors) {
        throw error
    }

    Object.keys(errors).forEach((key) => {
        form.setError(key, {
            message: errors[key],
        })
    })
}

export function addValidationOnError(form?: UseFormReturn<any>) {
    return (error: ValidationError<any>) => {
        if (!form) {
            return error
        }

        addValidationToForm(form, error)

        return error
    }
}
