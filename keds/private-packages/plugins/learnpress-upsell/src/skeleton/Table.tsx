
export default function TableSkeleton() {
	return (
		<div className="grid grid-cols-12 animate-pulse">
			<div className="col-span-4 font-semibold text-sm text-gray-700 px-5 py-3 border-b border-gray-200 overflow-hidden whitespace-nowrap text-ellipsis">
				<div className="w-36 h-4 bg-slate-200 rounded"></div>
			</div>
			<div className="col-span-3 text-sm text-gray-600 px-5 py-3 border-b border-gray-200 overflow-hidden whitespace-nowrap text-ellipsis">
				<div className="w-36 h-4 bg-slate-200 rounded"></div>
			</div>
			<div className="col-span-2 text-sm text-gray-500 px-5 py-3 border-b border-gray-200">
				<div className="w-12 h-4 bg-slate-200 rounded"></div>
			</div>
			<div className="col-span-1 text-sm text-gray-500 px-5 py-3 border-b border-gray-200">
				<div className="w-12 h-4 bg-slate-200 rounded"></div>
			</div>
			<div className="col-span-2 text-sm text-gray-500 px-5 py-3 border-b border-gray-200">
				<div className="w-12 h-4 bg-slate-200 rounded"></div>
			</div>
		</div>
	);
}
