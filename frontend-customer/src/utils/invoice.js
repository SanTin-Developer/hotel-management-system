import { jsPDF } from "jspdf";
import html2canvas from "html2canvas-pro";

export async function downloadInvoice(node, filename) {
  const source = node?.querySelector?.("[data-invoice-root]") ?? node;

  const container = document.createElement("div");
  container.style.position = "fixed";
  container.style.left = "0";
  container.style.top = "0";
  container.style.zIndex = "-10";
  container.style.pointerEvents = "none";
  container.style.background = "#ffffff";
  container.appendChild(source.cloneNode(true));
  document.body.appendChild(container);

  try {
    const canvas = await html2canvas(container, {
      scale: 2,
      backgroundColor: "#ffffff",
      useCORS: true,
    });

    const pdf = new jsPDF({ unit: "mm", format: "a4" });
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();

    const imgHeight = (canvas.height * pageWidth) / canvas.width;
    const img = canvas.toDataURL("image/png");

    let heightLeft = imgHeight;
    let position = 0;

    pdf.addImage(img, "PNG", 0, position, pageWidth, imgHeight);
    heightLeft -= pageHeight;

    while (heightLeft > 0) {
      position -= pageHeight;
      pdf.addPage();
      pdf.addImage(img, "PNG", 0, position, pageWidth, imgHeight);
      heightLeft -= pageHeight;
    }

    pdf.save(filename);
  } finally {
    container.remove();
  }
}