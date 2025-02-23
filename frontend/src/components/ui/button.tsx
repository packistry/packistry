import * as React from 'react'
import { Slot } from '@radix-ui/react-slot'
import { cva, type VariantProps } from 'class-variance-authority'
import { Loader2 } from 'lucide-react'

import { cn } from '@/lib/utils'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'

const buttonVariants = cva(
    'cursor-pointer inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground hover:bg-primary/90',
                destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
                outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
                ghost: 'hover:bg-accent hover:text-accent-foreground',
                link: 'text-primary underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-10 px-4 py-2',
                sm: 'h-9 rounded-md px-3',
                xs: 'h-6 rounded-md px-2',
                lg: 'h-11 rounded-md px-8',
                icon: 'h-10 w-10',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
)

export interface ButtonProps
    extends React.ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {
    asChild?: boolean
    loading?: boolean
    dangerous?: {
        title: string
        description: string
        confirm?: ButtonProps
        cancel?: ButtonProps
        className?: string
    }
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
    ({ className, variant, size, asChild = false, loading = false, dangerous, children, onClick, ...props }, ref) => {
        const Comp = asChild ? Slot : 'button'
        const [open, setOpen] = React.useState(false)

        const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
            if (dangerous) {
                setOpen(true)
            } else if (onClick) {
                onClick(e)
            }
        }

        const handleConfirm = (e: React.MouseEvent<HTMLButtonElement>) => {
            setOpen(false)
            if (onClick) {
                onClick(e)
            }
        }

        const buttonContent = loading ? (
            <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Loading
            </>
        ) : (
            children
        )

        if (dangerous) {
            return (
                <Popover
                    open={open}
                    onOpenChange={setOpen}
                >
                    <PopoverTrigger asChild>
                        <Comp
                            className={cn(buttonVariants({ variant, size, className }))}
                            ref={ref}
                            disabled={loading || props.disabled}
                            onClick={handleClick}
                            {...props}
                        >
                            {buttonContent}
                        </Comp>
                    </PopoverTrigger>
                    <PopoverContent className={cn('min-w-[325px]', dangerous.className)}>
                        <div className="grid gap-4">
                            <div className="space-y-2">
                                <h4 className="font-medium leading-none">{dangerous.title}</h4>
                                <p className="text-sm text-muted-foreground">{dangerous.description}</p>
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    {...dangerous.cancel}
                                    onClick={() => setOpen(false)}
                                >
                                    {dangerous.cancel?.children ? dangerous.cancel.children : 'Cancel'}
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    {...dangerous.confirm}
                                    onClick={handleConfirm}
                                >
                                    {dangerous.confirm?.children ? dangerous.confirm.children : 'Confirm'}
                                </Button>
                            </div>
                        </div>
                    </PopoverContent>
                </Popover>
            )
        }

        return (
            <Comp
                className={cn(buttonVariants({ variant, size, className }))}
                ref={ref}
                disabled={loading || props.disabled}
                onClick={handleClick}
                {...props}
            >
                {buttonContent}
            </Comp>
        )
    }
)
Button.displayName = 'Button'

export { Button, buttonVariants }
