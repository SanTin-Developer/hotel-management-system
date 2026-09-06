import { EmptyState, TableSkeleton } from "./States";

export default function DataTable({
  columns,
  data,
  loading,
  emptyTitle,
  emptyDescription,
  rowKey = "id",
  minWidth = 720,
  onRowDoubleClick,
}) {
  if (loading) return <TableSkeleton rows={6} />;

  if (!data?.length) {
    return (
      <EmptyState
        title={emptyTitle ?? "Nothing here yet"}
        description={emptyDescription}
      />
    );
  }

  return (
    <div className="overflow-hidden rounded-xl border border-[#DCE3D5] bg-white">
      <div className="overflow-x-auto">
        <table className="w-full text-sm" style={{ minWidth }}>
          <thead>
            <tr className="border-b border-[#EEF1E9] bg-[#F8F9F4]">
              {columns.map((col, i) => (
                <th
                  key={i}
                  className={`whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-[#5E6B5A] ${col.className ?? ""}`}
                  style={col.style}
                >
                  {col.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-[#EEF1E9]">
            {data.map((row) => (
              <tr
                key={row?.[rowKey]}
                onDoubleClick={onRowDoubleClick ? () => onRowDoubleClick(row) : undefined}
                title={onRowDoubleClick ? "Double-click to view details" : undefined}
                className={`transition-colors hover:bg-[#F8F9F4] ${
                  onRowDoubleClick ? "cursor-pointer" : ""
                }`}
              >
                {columns.map((col, i) => (
                  <td key={i} className={`px-5 py-3.5 align-middle ${col.className ?? ""}`}>
                    {col.cell ? col.cell(row) : row?.[col.accessor]}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}