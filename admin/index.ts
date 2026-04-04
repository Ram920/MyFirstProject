import { serve } from "https://deno.land/std@0.168.0/http/server.ts"
import { jsPDF } from "https://esm.sh/jspdf@2.5.1"

const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
}

serve(async (req) => {
  // Handle CORS preflight
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders })
  }

  try {
    const { customerName, quoteId, items, grandTotal, companyName } = await req.json()

    // Create a new PDF document
    const doc = new jsPDF();

    // Add Company Header
    doc.setFontSize(20);
    doc.text(companyName, 105, 20, { align: 'center' });
    
    doc.setFontSize(10);
    doc.text(`Quote #: ${quoteId}`, 20, 40);
    doc.text(`Customer: ${customerName}`, 20, 45);
    doc.text(`Date: ${new Date().toLocaleDateString()}`, 160, 40);

    // Draw a simple table header
    doc.line(20, 55, 190, 55);
    doc.text("Description", 25, 60);
    doc.text("Qty", 120, 60);
    doc.text("Rate", 145, 60);
    doc.text("Amount", 170, 60);
    doc.line(20, 63, 190, 63);

    // Add Items
    let y = 70;
    items.forEach((item: any) => {
      doc.text(item.description, 25, y);
      doc.text(item.qty.toString(), 120, y);
      doc.text(item.rate.toFixed(2), 145, y);
      doc.text(item.amount.toFixed(2), 170, y);
      y += 10;
    });

    // Footer
    doc.line(20, y, 190, y);
    doc.setFontSize(12);
    doc.text(`Grand Total: INR ${grandTotal}`, 170, y + 10, { align: 'right' });

    // Output as ArrayBuffer
    const pdfOutput = doc.output('arraybuffer');

    return new Response(pdfOutput, {
      headers: { ...corsHeaders, 'Content-Type': 'application/pdf' },
    })
  } catch (error) {
    return new Response(JSON.stringify({ error: error.message }), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' },
      status: 400,
    })
  }
})