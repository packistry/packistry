import { FormInput } from '@/components/form/elements/form-input'
import * as React from 'react'
import { UseFormReturn } from 'react-hook-form'
import { FormRadioGroup } from '@/components/form/elements/form-radio-group'
import { FormSwitch } from '@/components/form/elements/form-switch'
import {
    AuthenticationSource,
    StoreAuthenticationSourceInput,
    UpdateAuthenticationSourceInput,
} from '@/api/authentication-source'
import { FormAuthenticationProviderSelect } from '@/components/form/elements/form-authentication-provider-select'
import { AuthenticationProvider, providerIcons } from '@/api/authentication-provider'
import { FormAuthenticationProviderDomainList } from '@/components/form/elements/form-authentication-provider-domain-list'
import { RepositoryPackageTree } from '@/components/form/elements/repository-package-tree'

export function AuthenticationSourceFormElements({
    form,
    authenticationSource,
}: {
    form: UseFormReturn<StoreAuthenticationSourceInput | UpdateAuthenticationSourceInput>
    authenticationSource?: AuthenticationSource
}) {
    const role = form.watch('defaultUserRole')
    const iconUrl = form.watch('iconUrl')
    const provider = form.watch('provider')

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
                        description="Enter the URL where the OpenID configuration can be found (e.g., https://company.okta.com/.well-known/openid-configuration)."
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
                            description="Enter the URL of an icon. Publicly visible on the sign-in page."
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
                <FormAuthenticationProviderDomainList
                    label="Allowed domains"
                    description="Domains that are allowed to authenticate with this source. If left empty, all domains are allowed."
                    name="allowedDomains"
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
                    <RepositoryPackageTree
                        label="Repositories & Packages"
                        repositoriesName="defaultUserRepositories"
                        packagesName="defaultUserPackages"
                        description="Default repositories that users can access upon their first authentication with this source."
                        packageRepositoryMap={
                            authenticationSource?.packages?.reduce<Record<string, string>>((accumulator, pkg) => {
                                accumulator[String(pkg.id)] = String(pkg.repositoryId)
                                return accumulator
                            }, {}) || {}
                        }
                        control={form.control}
                    />
                )}
                <FormSwitch
                    label="Allow Registration"
                    description="Allow or reject new user registration on this authentication source. When deactivated, new users will be unable to sign in using this source."
                    name="allowRegistration"
                    control={form.control}
                />
                <FormSwitch
                    label="Active"
                    description="Enable or disable this authentication source. When deactivated, users will be unable to sign in using this source. Personal tokens from users using this source will remain active."
                    name="active"
                    control={form.control}
                />
            </div>
        </div>
    )
}
