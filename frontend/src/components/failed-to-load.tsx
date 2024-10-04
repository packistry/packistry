import * as React from 'react'
import { Card, CardContent } from '@/components/ui/card'
import { AlertTriangle } from 'lucide-react'

export default function FailedToLoad() {
    return (
        <Card className="w-full max-w-md bg-background border-gray-700 mx-auto">
            <CardContent className="p-6">
                <div className="flex flex-col items-center text-center space-y-4">
                    <div className="relative">
                        <div className="absolute inset-0 bg-red-500 opacity-20 blur-xl rounded-full"></div>
                        <AlertTriangle className="relative z-10 w-16 h-16 text-red-500" />
                    </div>
                    <h2 className="text-2xl font-bold">Oops! Something went wrong</h2>
                    <p className="text-gray-400 max-w-xs">
                        We could not load the data you requested. Please try again later.
                    </p>
                </div>
            </CardContent>
        </Card>
    )
}
