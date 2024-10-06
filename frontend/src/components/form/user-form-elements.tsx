import { FormInput } from '@/components/form/elements/FormInput'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormRepositorySearchCheckboxGroup } from '@/components/form/elements/FormRepositorySearchCheckboxGroup'
import { FormRadioGroup } from '@/components/form/elements/FormRadioGroup'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function UserFormElements({ form }: { form: UseFormReturn<any, any, any> }) {
    const role = form.watch('role')

    return (
        <>
            <FormInput
                label="Name"
                name="name"
                control={form.control}
            />
            <FormInput
                label="Email"
                name="email"
                control={form.control}
            />
            <FormRadioGroup
                label="Role"
                name="role"
                options={[
                    { value: 'admin', label: 'Admin' },
                    { value: 'user', label: 'User' },
                ]}
                control={form.control}
            />
            {role === 'user' && (
                <FormRepositorySearchCheckboxGroup
                    label="Private Repositories"
                    description="Give user access to private repositories"
                    control={form.control}
                />
            )}
        </>
    )
}
