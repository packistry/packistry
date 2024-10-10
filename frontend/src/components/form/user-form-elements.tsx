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
                description="Enter the name of ther user to be added."
                control={form.control}
            />
            <FormInput
                label="Email"
                name="email"
                description="Provide a unique email for the user"
                control={form.control}
            />
            <FormInput
                label="Password"
                name="password"
                description="Enter a password for this user"
                type="password"
                control={form.control}
            />
            <FormRadioGroup
                label="Role"
                name="role"
                options={[
                    { value: 'admin', label: 'Admin: Full access' },
                    { value: 'user', label: 'User: Limited access to view assigned private repositories' },
                ]}
                control={form.control}
            />
            {role === 'user' && (
                <FormRepositorySearchCheckboxGroup
                    label="Private Repositories"
                    description="Give user access to private repositories"
                    control={form.control}
                    filters={{
                        public: false,
                    }}
                />
            )}
        </>
    )
}
