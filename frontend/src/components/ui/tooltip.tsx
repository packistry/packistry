import * as React from 'react'
import { ReactNode } from 'react'
import * as TooltipPrimitive from '@radix-ui/react-tooltip'
import { TooltipProps } from '@radix-ui/react-tooltip'

import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { toast } from 'sonner'
import { ClipboardIcon } from 'lucide-react'

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
}: {
    disabled?: boolean
    content: ReactNode
    children: ReactNode
    tooltip?: TooltipProps
}) => {
    return (
        <TooltipProvider>
            <Tooltip
                delayDuration={0}
                {...tooltip}
            >
                <TooltipTrigger type="button">{children}</TooltipTrigger>
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
    return (
        <TextTooltip
            content={
                <>
                    Copy command to clipboard <br />
                    <code className="mt-2 block">{command}</code>
                </>
            }
        >
            <Button
                variant="outline"
                className="text-xs"
                size="sm"
                onClick={() =>
                    navigator.clipboard.writeText(command).then(() => {
                        toast('Copied command to clipboard')
                    })
                }
            >
                <ClipboardIcon size={15} />
            </Button>
        </TextTooltip>
    )
}
export { Tooltip, TooltipTrigger, TooltipContent, TooltipProvider, TextTooltip, CopyCommandTooltip }
