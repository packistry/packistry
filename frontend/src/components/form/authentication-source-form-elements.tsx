import { FormInput } from '@/components/form/elements/form-input'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormRepositorySearchCheckboxGroup } from '@/components/form/elements/form-repository-search-checkbox-group'
import { FormRadioGroup } from '@/components/form/elements/form-radio-group'
import { FormSwitch } from '@/components/form/elements/form-switch'
import { StoreAuthenticationSourceInput, UpdateAuthenticationSourceInput } from '@/api/authentication-source'
import { FormAuthenticationProviderSelect } from '@/components/form/elements/form-authentication-provider-select'
import { AuthenticationProvider, providerIcons } from '@/api/authentication-provider'
import {
    FormAuthenticationProviderDomainCheckboxGroup
} from "@/components/form/elements/form-authentication-provider-domain";

export function AuthenticationSourceFormElements({
    form,
}: {
    form: UseFormReturn<StoreAuthenticationSourceInput | UpdateAuthenticationSourceInput>
}) {
    const role = form.watch('defaultUserRole')
    const iconUrl = form.watch('iconUrl')
    const provider = form.watch('provider')
    const domain = form.watch('allowedDomains')

    return (
        <div className="flex gap-6">
            <div className="space-y-4 min-w-[470px]">
                <FormInput
                    label="Name"
                    name="name"
                    description="Enter a name for this authentication source to easily identify it. Publicly visible on sign in page."
                    control={form.control}
                />

                <FormAuthenticationProviderSelect
                    label="Provider"
                    name="provider"
                    description="Provider e.g. OIDC, GitHub etc.."
                    control={form.control}
                    onChange={(provider) => {
                        form.setValue('iconUrl', providerIcons[provider as AuthenticationProvider])
                    }}
                />

                {provider === 'oidc' && (
                    <FormInput
                        label="OpenID Connect Auto Discovery URL"
                        name="discoveryUrl"
                        onChange={(event) => {
                            try {
                                const parsedUrl = new URL(event.target.value)
                                form.setValue('iconUrl', `${parsedUrl.origin}/favicon.ico`)
                            } catch {
                                //
                            }
                        }}
                        description="Enter the url where the OpenID configuration can be found. e.g. https://company.okta.com/.well-known/openid-configuration"
                        control={form.control}
                    />
                )}

                <FormInput
                    label="Client ID"
                    name="clientId"
                    control={form.control}
                />
                <FormInput
                    label="Client Secret"
                    name="clientSecret"
                    control={form.control}
                />
                <div className="flex items-center gap-2 relative">
                    <div className="w-full">
                        <FormInput
                            label="Icon URL"
                            name="iconUrl"
                            description="Enter the url of an icon. Publicly visible on the sign in page."
                            control={form.control}
                        />
                    </div>
                    {iconUrl && (
                        <img
                            className="max-h-6 max-w-6 -mt-1"
                            alt="Invalid icon URL"
                            src={iconUrl}
                        />
                    )}
                </div>
                <FormAuthenticationProviderDomainCheckboxGroup
                    label="Allowed domains"
                    description="Allowed domains that able to authentication with this source."
                    name="allowedDomains"
                    options={domain || []}
                    control={form.control}
                />
            </div>

            <div className="space-y-4">
                <FormRadioGroup
                    label="Role"
                    description="Default role assigned to users upon their first authentication with this source."
                    name="defaultUserRole"
                    options={[
                        { value: 'admin', label: 'Admin: Full access' },
                        { value: 'user', label: 'User: Limited access to view assigned private repositories' },
                    ]}
                    control={form.control}
                />
                {role === 'user' && (
                    <FormRepositorySearchCheckboxGroup
                        label="Repositories"
                        name="defaultUserRepositories"
                        description="Default repositories that users can access upon their first authentication with this source."
                        control={form.control}
                    />
                )}
                <FormSwitch
                    label="Active"
                    description="Enable or disable this authentication source. When deactivated, users will be unable to sign in using this source. Personal tokens from users using this source will remain active."
                    name="active"
                    control={form.control}
                />
                <FormSwitch
                    label="Allow Registraion"
                    description="Allow or reject new user registraion on this authentication source. When deactivated, new users will be unable to sign in using this source."
                    name="allowRegistration"
                    control={form.control}
                />
            </div>
        </div>
    )
}
