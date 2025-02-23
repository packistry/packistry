import * as React from 'react'
import { ReactNode, useCallback, useState } from 'react'
import * as TooltipPrimitive from '@radix-ui/react-tooltip'
import { TooltipProps } from '@radix-ui/react-tooltip'

import { cn } from '@/lib/utils'
import { toast } from 'sonner'
import { CheckIcon, ClipboardIcon } from 'lucide-react'

const TooltipProvider = TooltipPrimitive.Provider

const Tooltip = TooltipPrimitive.Root

const TooltipTrigger = TooltipPrimitive.Trigger

const TooltipContent = React.forwardRef<
    React.ElementRef<typeof TooltipPrimitive.Content>,
    React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Content>
>(({ className, sideOffset = 4, ...props }, ref) => (
    <TooltipPrimitive.Content
        ref={ref}
        sideOffset={sideOffset}
        className={cn(
            'z-50 overflow-hidden rounded-md border bg-popover px-3 py-1.5 text-sm text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
            className
        )}
        {...props}
    />
))
TooltipContent.displayName = TooltipPrimitive.Content.displayName

const TextTooltip = ({
    disabled = false,
    content,
    tooltip,
    children,
    onClick,
}: {
    disabled?: boolean
    content: ReactNode
    children: ReactNode
    tooltip?: TooltipProps
    onClick?: () => void
}) => {
    return (
        <TooltipProvider>
            <Tooltip
                delayDuration={0}
                {...tooltip}
            >
                <TooltipTrigger
                    onClick={onClick}
                    className="cursor-pointer"
                    type="button"
                >
                    {children}
                </TooltipTrigger>
                {!disabled && (
                    <TooltipContent>
                        <p>{content}</p>
                    </TooltipContent>
                )}
            </Tooltip>
        </TooltipProvider>
    )
}

const CopyCommandTooltip = ({ command }: { command: string }) => {
    const [copied, setCopied] = useState(false)

    const handleCopy = useCallback(() => {
        navigator.clipboard.writeText(command).then(() => {
            setCopied(true)
            toast('Copied command to clipboard')

            setTimeout(() => setCopied(false), 2000)
        })
    }, [command])

    return (
        <TextTooltip
            onClick={handleCopy}
            content={
                <>
                    Copy command to clipboard <br />
                    <code className="mt-2 block">{command}</code>
                </>
            }
        >
            {copied ? <CheckIcon size={15} /> : <ClipboardIcon size={15} />}
        </TextTooltip>
    )
}
export { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider, TextTooltip, CopyCommandTooltip }
