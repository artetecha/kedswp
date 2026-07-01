import useToast from "@/hooks/useToast";
import { __ } from "@/utils/i18n";
import apiFetch from "@wordpress/api-fetch";
import { useState } from "@wordpress/element";
import { Popover } from "@headlessui/react";
import useMatchMutate from "@/hooks/useMatchMutate";
import EditPackage from "./EditPackage";

const STATUS = {
	draft: {
		label: __("Draft"),
		bg: "bg-gray-200",
		color: "text-gray-700",
	},
	trash: {
		label: __("Trash"),
		bg: "bg-red-400",
		color: "text-white",
	},
	pending: {
		label: __("Pending"),
		bg: "bg-yellow-400",
		color: "text-white",
	},
	future: {
		label: __("Scheduled"),
		bg: "bg-blue-400",
		color: "text-white",
	},
	private: {
		label: __("Private"),
		bg: "bg-gray-400",
		color: "text-white",
	},
};

export default function TablePackage({ packages }) {
	const [packageId, setPackageId] = useState(0);
	const [loading, setLoading] = useState("");

	const matchMutate = useMatchMutate();

	const addToast = useToast();

	const onDeletePackage = async (id, trash = true) => {
		setLoading("delete");

		try {
			const response = await apiFetch({
				path: "/learnpress-package/v1/admin/delete",
				method: "POST",
				data: {
					id,
					trash,
				},
			});

			if (!response.success) {
				throw new Error(response?.message || "Something went wrong");
			} else {
				addToast(response?.message || "Success", "success");
			}

			matchMutate(/^\/learnpress-package\/v1\/admin\/packages/);
		} catch (e) {
			addToast(e.message, "error");
		}

		setLoading("");
	};

	return (
		<div className="px-0">
			<div className="relative rounded border border-gray-200 border-solid">
				<table className="w-full table-fixed border-separate border-spacing-0">
					<thead>
						<tr>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid rounded-tl">
								{__("Title")}
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{__("Courses")}
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{__("Price")}
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{__("Author")}
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid rounded-tr">
								{__("Date modified")}
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid rounded-tr"></th>
						</tr>
					</thead>
					<tbody className="[&>tr:last-child>td]:border-b-0">
						{packages.map((pkg) => (
							<tr key={pkg.id}>
								<td className="px-6 py-4 whitespace-normal break-words border-b max-w-[300px] border-x-0 border-t-0 border-gray-200 border-solid align-top">
									<div
										className="flex items-center text-gray-800 hover:text-gray-600 cursor-pointer break-words"
										onClick={() => setPackageId(pkg.id)}
										role="button"
										tabIndex={0}
									>
										{pkg.title || "(no title)"}

										{STATUS?.[pkg.status]?.label && (
											<span
												className={`ml-2 text-xs px-2 py-1 leading-4 rounded ${
													STATUS?.[pkg.status]?.bg ||
													""
												} ${
													STATUS?.[pkg.status]
														?.color || ""
												}`}
											>
												{STATUS?.[pkg.status]?.label}
											</span>
										)}
									</div>
								</td>

								<td className="max-w-[300px] px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
									<ul className="flex flex-col space-y-2 list-disc m-0 p-0">
										{pkg.courses.map((course) => (
											<li key={course.id}>
												<a
													href={
														LP_UPSELL_LOCALIZE.admin_url +
														"post.php?post=" +
														course.id +
														"&action=edit"
													}
													className="block truncate no-underline text-gray-500 hover:text-gray-700 outline-none p-0 m-0 cursor-pointer"
													target="_blank"
													rel="noreferrer noreferrer"
												>
													{course.title}
												</a>
											</li>
										))}
									</ul>
								</td>
								<td
									className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top [&>ins]:no-underline"
									dangerouslySetInnerHTML={{
										__html: pkg.price || "-",
									}}
								></td>
								<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
									{pkg.author}
								</td>
								<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
									{pkg.date}
								</td>
								<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
									<div className="flex items-center gap-x-2">
										<button
											onClick={() => setPackageId(pkg.id)}
											className="relative group h-8 w-8 inline-flex items-center justify-center font-medium text-sm text-gray-500 border border-gray-200 border-solid rounded hover:bg-indigo-50 shadow-sm bg-transparent cursor-pointer"
										>
											<div className=" opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-1 absolute bottom-full z-20 bg-gray-800 text-white text-sm rounded px-2.5 py-1">
												{__("Edit")}
											</div>
											<svg
												xmlns="http://www.w3.org/2000/svg"
												className="h-4 w-4"
												fill="none"
												viewBox="0 0 24 24"
												stroke="currentColor"
												strokeWidth={2}
											>
												<path
													strokeLinecap="round"
													strokeLinejoin="round"
													d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
												/>
											</svg>
										</button>
										<a
											href={pkg.permalink}
											target="_blank"
											rel="noopener noreferrer"
											className="relative group h-8 w-8 inline-flex items-center justify-center font-medium text-sm text-gray-500 border border-gray-200 border-solid rounded hover:bg-indigo-50 shadow-sm bg-transparent cursor-pointer"
										>
											<div className=" opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-1 absolute bottom-full z-20 bg-gray-800 text-white text-sm rounded px-2.5 py-1">
												{__("View")}
											</div>
											<svg
												xmlns="http://www.w3.org/2000/svg"
												className="h-4 w-4"
												fill="none"
												viewBox="0 0 24 24"
												stroke="currentColor"
												strokeWidth={2}
											>
												<path
													strokeLinecap="round"
													strokeLinejoin="round"
													d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
												/>
											</svg>
										</a>
										<Popover className="relative">
											{({ open }) => (
												<>
													<Popover.Button className="relative group h-8 w-8 inline-flex items-center justify-center font-medium text-sm text-gray-500 border border-gray-200 border-solid rounded hover:bg-indigo-50 shadow-sm bg-transparent cursor-pointer">
														<div className=" opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-1 absolute bottom-full z-20 bg-gray-800 text-white text-sm rounded px-2.5 py-1">
															{__("Delete")}
														</div>
														<svg
															xmlns="http://www.w3.org/2000/svg"
															className="h-4 w-4"
															fill="none"
															viewBox="0 0 24 24"
															stroke="currentColor"
															strokeWidth={2}
														>
															<path
																strokeLinecap="round"
																strokeLinejoin="round"
																d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
															/>
														</svg>
													</Popover.Button>
													<Popover.Panel className="absolute z-50 bottom-full w-48 mb-3 right-0 px-0">
														<div className="text-center overflow-hidden p-6 bg-gray-800 rounded-md shadow-xl ring-1 ring-black ring-opacity-5">
															<div className="relative text-sm text-gray-200 font-semibold mb-4">
																{__(
																	"Are you sure?"
																)}
															</div>

															{loading ===
															"delete" ? (
																<span className="bg-gray-800 rounded-md">
																	<span className="flex items-center justify-center">
																		<svg
																			className="animate-spin h-5 w-5 text-white"
																			xmlns="http://www.w3.org/2000/svg"
																			fill="none"
																			viewBox="0 0 24 24"
																		>
																			<circle
																				className="opacity-25"
																				cx="12"
																				cy="12"
																				r="10"
																				stroke="currentColor"
																				strokeWidth="4"
																			></circle>
																			<path
																				className="opacity-75"
																				fill="currentColor"
																				d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
																			></path>
																		</svg>
																	</span>
																</span>
															) : (
																<div className="flex items-center gap-x-2 justify-center">
																	{pkg.status !==
																		"trash" && (
																		<button
																			onClick={() =>
																				onDeletePackage(
																					pkg.id,
																					true
																				)
																			}
																			className="px-2 py-1 leading-4 rounded-sm border-none shadow-none bg-gray-200 text-xs font-medium text-red-600 cursor-pointer"
																		>
																			{__(
																				"Trash"
																			)}
																		</button>
																	)}
																	<button
																		onClick={() =>
																			onDeletePackage(
																				pkg.id,
																				false
																			)
																		}
																		className="px-2 py-1 leading-4 rounded-sm border-none shadow-none bg-gray-200 text-xs font-medium text-red-600 cursor-pointer"
																	>
																		{__(
																			"Delete"
																		)}
																	</button>
																</div>
															)}
														</div>
													</Popover.Panel>
												</>
											)}
										</Popover>
									</div>
								</td>
							</tr>
						))}
					</tbody>
				</table>
			</div>
			{!!packageId && (
				<EditPackage
					packageId={packageId}
					setPackageId={setPackageId}
				/>
			)}
		</div>
	);
}
