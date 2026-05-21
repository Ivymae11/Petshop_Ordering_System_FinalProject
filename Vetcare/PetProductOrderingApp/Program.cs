using System;
using System.Collections;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Net;
using System.Web.Script.Serialization;
using System.Windows.Forms;

namespace PetProductOrderingApp
{
    internal static class Program
    {
        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new LoginForm());
        }
    }

    public class ComboItem
    {
        public string Text;
        public string Value;
        public Dictionary<string, object> Data;
        public ComboItem(string text, string value, Dictionary<string, object> data) { Text = text; Value = value; Data = data; }
        public override string ToString() { return Text; }
    }

    public class CartLine
    {
        public int ProductId;
        public string ProductName;
        public int Quantity;
        public decimal UnitPrice;
        public decimal LineTotal;
    }

    public class LoginForm : Form
    {
        private string apiUrl = "http://localhost:8000/api.php";
        private JavaScriptSerializer json = new JavaScriptSerializer();
        private TextBox txtName = new TextBox();
        private TextBox txtEmail = new TextBox();
        private TextBox txtPassword = new TextBox();
        private TextBox txtContact = new TextBox();
        private TextBox txtAddress = new TextBox();
        private Label lblStatus = new Label();
        private Color coffee = Color.FromArgb(247, 244, 255);
        private Color orange = Color.FromArgb(139, 110, 232);

        public LoginForm()
        {
            Text = "Paws & Care Customer Access";
            StartPosition = FormStartPosition.CenterScreen;
            Size = new Size(820, 520);
            FormBorderStyle = FormBorderStyle.FixedSingle;
            MaximizeBox = false;
            BackColor = Color.FromArgb(248, 245, 255);
            Font = new Font("Segoe UI", 9);

            Panel hero = new Panel(); hero.Location = new Point(0, 0); hero.Size = new Size(390, 520); hero.BackColor = Color.FromArgb(248,245,255); Controls.Add(hero);
            Label mark = new Label(); mark.Text = "🐾"; mark.BackColor = Color.FromArgb(240, 234, 255); mark.ForeColor = Color.FromArgb(124, 91, 213); mark.TextAlign = ContentAlignment.MiddleCenter; mark.Font = new Font("Segoe UI", 18, FontStyle.Bold); mark.Location = new Point(34, 40); mark.Size = new Size(82, 58); hero.Controls.Add(mark);
            Label h = new Label(); h.Text = "Care essentials\nfor every pet."; h.ForeColor = Color.FromArgb(33, 27, 50); h.Font = new Font("Segoe UI", 24, FontStyle.Bold); h.Location = new Point(34, 130); h.Size = new Size(310, 110); hero.Controls.Add(h);
            Label hp = new Label(); hp.Text = "Browse products, build your cart, submit orders, and track each request."; hp.ForeColor = Color.FromArgb(92, 84, 112); hp.Font = new Font("Segoe UI", 10, FontStyle.Bold); hp.Location = new Point(36, 260); hp.Size = new Size(300, 80); hero.Controls.Add(hp);

            Panel form = new Panel(); form.Location = new Point(430, 42); form.Size = new Size(330, 410); form.BackColor = Color.White; form.BorderStyle = BorderStyle.FixedSingle; Controls.Add(form);
            Label title = new Label(); title.Text = "Customer Login"; title.ForeColor = Color.FromArgb(33, 27, 50); title.Font = new Font("Segoe UI", 20, FontStyle.Bold); title.Location = new Point(24, 22); title.Size = new Size(280, 36); form.Controls.Add(title);
            AddLabel(form, "Full Name", 24, 76); txtName.Location = new Point(24, 98); txtName.Size = new Size(280, 27); txtName.Text = "Mia Pet Owner"; form.Controls.Add(txtName);
            AddLabel(form, "Email", 24, 132); txtEmail.Location = new Point(24, 154); txtEmail.Size = new Size(280, 27); txtEmail.Text = "customer@petshop.test"; form.Controls.Add(txtEmail);
            AddLabel(form, "Password", 24, 188); txtPassword.Location = new Point(24, 210); txtPassword.Size = new Size(280, 27); txtPassword.PasswordChar = '*'; txtPassword.Text = "customer123"; form.Controls.Add(txtPassword);
            AddLabel(form, "Contact", 24, 244); txtContact.Location = new Point(24, 266); txtContact.Size = new Size(132, 27); txtContact.Text = "09170000002"; form.Controls.Add(txtContact);
            AddLabel(form, "Address", 172, 244); txtAddress.Location = new Point(172, 266); txtAddress.Size = new Size(132, 27); txtAddress.Text = "Quezon City"; form.Controls.Add(txtAddress);
            Button login = new Button(); login.Text = "LOGIN"; login.Location = new Point(24, 318); login.Size = new Size(132, 38); StyleButton(login, orange); login.Click += delegate { LoginCustomer(); }; form.Controls.Add(login);
            Button register = new Button(); register.Text = "REGISTER"; register.Location = new Point(172, 318); register.Size = new Size(132, 38); StyleButton(register, Color.FromArgb(20, 184, 166)); register.Click += delegate { RegisterCustomer(); }; form.Controls.Add(register);
            lblStatus.ForeColor = orange; lblStatus.TextAlign = ContentAlignment.MiddleCenter; lblStatus.Location = new Point(430, 462); lblStatus.Size = new Size(330, 28); lblStatus.Font = new Font("Segoe UI", 8, FontStyle.Bold); Controls.Add(lblStatus);
        }

        private void AddLabel(Control parent, string text, int x, int y) { Label label = new Label(); label.Text = text; label.Location = new Point(x, y); label.Size = new Size(150, 20); label.Font = new Font("Segoe UI", 8, FontStyle.Bold); label.ForeColor = Color.FromArgb(75, 53, 39); parent.Controls.Add(label); }
        private void StyleButton(Button b, Color color)
        {
            b.UseVisualStyleBackColor = false;
            b.BackColor = color;
            b.ForeColor = Color.White;
            b.FlatStyle = FlatStyle.Flat;
            b.FlatAppearance.BorderSize = 0;
            b.Font = new Font("Segoe UI", 8, FontStyle.Bold);
            b.Cursor = Cursors.Hand;
        }

        private string PostApi(Dictionary<string, string> data)
        {
            using (WebClient client = new WebClient())
            {
                client.Headers[HttpRequestHeader.ContentType] = "application/x-www-form-urlencoded";
                List<string> parts = new List<string>();
                foreach (KeyValuePair<string, string> item in data) parts.Add(Uri.EscapeDataString(item.Key) + "=" + Uri.EscapeDataString(item.Value));
                try { return client.UploadString(apiUrl, "POST", string.Join("&", parts.ToArray())); }
                catch (WebException ex) { throw new Exception(ReadError(ex)); }
            }
        }
        private string ReadError(WebException ex)
        {
            try { if (ex.Response != null) { using (Stream stream = ex.Response.GetResponseStream()) using (StreamReader reader = new StreamReader(stream)) { string body = reader.ReadToEnd(); Dictionary<string, object> data = json.Deserialize<Dictionary<string, object>>(body); if (data != null && data.ContainsKey("message")) return Convert.ToString(data["message"]); return body; } } } catch { }
            return ex.Message;
        }
        private void LoginCustomer()
        {
            try
            {
                string response = PostApi(new Dictionary<string, string>{{"action","login"},{"email",txtEmail.Text.Trim()},{"password",txtPassword.Text.Trim()},{"role","customer"}});
                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response);
                Dictionary<string, object> user = result["user"] as Dictionary<string, object>;
                MainForm main = new MainForm(Convert.ToInt32(user["id"]), Convert.ToString(user["name"]), Convert.ToString(user["email"]), Convert.ToString(user["address"]));
                main.FormClosed += delegate { if (Convert.ToString(main.Tag) == "logout") { txtPassword.Clear(); lblStatus.Text = "Logged out."; Show(); } else Close(); };
                Hide(); main.Show();
            }
            catch (Exception ex) { MessageBox.Show("Login failed.\n\n" + ex.Message + "\n\nMake sure PHP is running:\nphp -S localhost:8000"); }
        }
        private void RegisterCustomer()
        {
            string email = txtEmail.Text.Trim().ToLower();
            if (txtName.Text.Trim() == "" || email == "" || txtPassword.Text.Trim() == "") { MessageBox.Show("Name, email, and password are required."); return; }
            if (email == "customer@petshop.test") { MessageBox.Show("Use a different email. The default customer account already exists."); return; }
            try
            {
                string response = PostApi(new Dictionary<string, string>{{"action","register_customer"},{"name",txtName.Text.Trim()},{"email",email},{"password",txtPassword.Text.Trim()},{"contact",txtContact.Text.Trim()},{"address",txtAddress.Text.Trim()}});
                Dictionary<string, object> result = json.Deserialize<Dictionary<string, object>>(response);
                MessageBox.Show(Convert.ToString(result["message"]));
            }
            catch (Exception ex) { MessageBox.Show("Registration failed.\n\n" + ex.Message); }
        }
    }

    public class MainForm : Form
    {
        private string apiUrl = "http://localhost:8000/api.php";
        private JavaScriptSerializer json = new JavaScriptSerializer();
        private int customerId; private string customerName; private string customerEmail; private string customerAddress;
        private DataGridView gridProducts = new DataGridView(); private DataGridView gridCart = new DataGridView(); private DataGridView gridOrders = new DataGridView(); private DataGridView gridOrderItems = new DataGridView();
        private ComboBox cboProduct = new ComboBox(); private NumericUpDown numQty = new NumericUpDown(); private NumericUpDown numDeliveryFee = new NumericUpDown(); private ComboBox cboPaymentMethod = new ComboBox(); private TextBox txtAddress = new TextBox(); private TextBox txtNotes = new TextBox();
        private Label lblCartTotal = new Label(); private Label lblStatus = new Label(); private List<CartLine> cart = new List<CartLine>();
        private Color coffee = Color.FromArgb(247, 244, 255); private Color orange = Color.FromArgb(139, 110, 232); private Color cream = Color.FromArgb(248, 245, 255);
        public MainForm(int id, string name, string email, string address)
        {
            customerId=id; customerName=name; customerEmail=email; customerAddress=address;
            Text="Paws & Care Product Ordering"; StartPosition=FormStartPosition.CenterScreen; Size=new Size(1180,720); FormBorderStyle=FormBorderStyle.FixedSingle; MaximizeBox=false; BackColor=cream; Font=new Font("Segoe UI",9); BuildUi(); Load += delegate { RefreshAll(); };
        }
        private void BuildUi()
        {
            Panel header = new Panel(); header.Location = new Point(0,0); header.Size = new Size(1180,76); header.BackColor = Color.White; header.BorderStyle = BorderStyle.FixedSingle; Controls.Add(header);
            Label logo = new Label(); logo.Text="🐾"; logo.BackColor=Color.FromArgb(240,234,255); logo.ForeColor=Color.FromArgb(124,91,213); logo.TextAlign=ContentAlignment.MiddleCenter; logo.Font=new Font("Segoe UI",16,FontStyle.Bold); logo.Location=new Point(20,16); logo.Size=new Size(70,44); header.Controls.Add(logo);
            Label title = new Label(); title.Text="Paws & Care Customer Shop"; title.ForeColor=Color.FromArgb(33,27,50); title.Font=new Font("Segoe UI",18,FontStyle.Bold); title.Location=new Point(105,14); title.Size=new Size(360,28); header.Controls.Add(title);
            Label prof = new Label(); prof.Text=customerName+"  /  "+customerEmail; prof.ForeColor=Color.FromArgb(92,84,112); prof.Font=new Font("Segoe UI",8,FontStyle.Bold); prof.Location=new Point(108,45); prof.Size=new Size(520,24); header.Controls.Add(prof);
            Button refresh = new Button(); refresh.Text="REFRESH"; refresh.Location=new Point(865,21); refresh.Size=new Size(92,34); StyleButton(refresh,orange); refresh.Click += delegate { RefreshAll(); }; header.Controls.Add(refresh);
            Button clearCart = new Button(); clearCart.Text="CLEAR CART"; clearCart.Location=new Point(965,21); clearCart.Size=new Size(92,34); StyleButton(clearCart,Color.FromArgb(104,88,128)); clearCart.Click += delegate { ClearCart(); }; header.Controls.Add(clearCart);
            Button logout = new Button(); logout.Text="LOGOUT"; logout.Location=new Point(1065,21); logout.Size=new Size(92,34); StyleButton(logout,Color.FromArgb(190,18,60)); logout.Click += delegate { if(MessageBox.Show("Logout and return to login?","Logout",MessageBoxButtons.YesNo)==DialogResult.Yes){ Tag="logout"; Close(); } }; header.Controls.Add(logout);

            Panel catalog = Card(20,96,535,285,"Browse Products"); Controls.Add(catalog); gridProducts.Location=new Point(18,54); gridProducts.Size=new Size(500,205); ConfigureGrid(gridProducts); catalog.Controls.Add(gridProducts);
            Panel cartPanel = Card(575,96,565,285,"Cart and Checkout"); Controls.Add(cartPanel);
            AddLabel(cartPanel,"Product",18,55); cboProduct.Location=new Point(82,52); cboProduct.Size=new Size(290,27); cboProduct.DropDownStyle=ComboBoxStyle.DropDownList; cartPanel.Controls.Add(cboProduct);
            AddLabel(cartPanel,"Qty",386,55); numQty.Location=new Point(425,52); numQty.Size=new Size(55,27); numQty.Minimum=1; numQty.Maximum=99; numQty.Value=1; cartPanel.Controls.Add(numQty);
            Button add = new Button(); add.Text="ADD"; add.Location=new Point(492,51); add.Size=new Size(55,30); StyleButton(add,orange); add.Click += delegate { AddToCart(); }; cartPanel.Controls.Add(add);
            gridCart.Location=new Point(18,92); gridCart.Size=new Size(340,125); ConfigureGrid(gridCart); cartPanel.Controls.Add(gridCart);
            AddLabel(cartPanel,"Delivery",375,96); numDeliveryFee.Location=new Point(448,93); numDeliveryFee.Size=new Size(100,27); numDeliveryFee.DecimalPlaces=2; numDeliveryFee.Maximum=10000; numDeliveryFee.Value=80; numDeliveryFee.ValueChanged += delegate { UpdateCartTotal(); }; cartPanel.Controls.Add(numDeliveryFee);
            AddLabel(cartPanel,"Payment",375,131); cboPaymentMethod.Location=new Point(448,128); cboPaymentMethod.Size=new Size(100,27); cboPaymentMethod.DropDownStyle=ComboBoxStyle.DropDownList; cboPaymentMethod.Items.Add("Cash on Pickup"); cboPaymentMethod.Items.Add("Cash on Delivery"); cboPaymentMethod.Items.Add("GCash"); cboPaymentMethod.Items.Add("Bank Transfer"); cboPaymentMethod.SelectedIndex=0; cartPanel.Controls.Add(cboPaymentMethod);
            AddLabel(cartPanel,"Address",375,166); txtAddress.Location=new Point(448,163); txtAddress.Size=new Size(100,27); txtAddress.Text=customerAddress; cartPanel.Controls.Add(txtAddress);
            AddLabel(cartPanel,"Notes",375,201); txtNotes.Location=new Point(448,198); txtNotes.Size=new Size(100,27); cartPanel.Controls.Add(txtNotes);
            Button remove = new Button(); remove.Text="REMOVE"; remove.Location=new Point(18,225); remove.Size=new Size(90,32); StyleButton(remove,Color.FromArgb(91,57,37)); remove.Click += delegate { RemoveSelectedCartItem(); }; cartPanel.Controls.Add(remove);
            Button checkout = new Button(); checkout.Text="CHECKOUT"; checkout.Location=new Point(458,235); checkout.Size=new Size(90,32); StyleButton(checkout,orange); checkout.Click += delegate { Checkout(); }; cartPanel.Controls.Add(checkout);
            lblCartTotal.Location=new Point(118,229); lblCartTotal.Size=new Size(320,24); lblCartTotal.ForeColor=orange; lblCartTotal.Font=new Font("Segoe UI",8,FontStyle.Bold); cartPanel.Controls.Add(lblCartTotal);

            Panel orders = Card(20,400,1120,215,"Orders and Item Details"); Controls.Add(orders); gridOrders.Location=new Point(18,54); gridOrders.Size=new Size(680,135); ConfigureGrid(gridOrders); gridOrders.SelectionChanged += delegate { LoadSelectedOrderItems(); }; orders.Controls.Add(gridOrders);
            gridOrderItems.Location=new Point(715,54); gridOrderItems.Size=new Size(385,135); ConfigureGrid(gridOrderItems); orders.Controls.Add(gridOrderItems);
            Button cancel = new Button(); cancel.Text="CANCEL SELECTED"; cancel.Location=new Point(945,16); cancel.Size=new Size(155,30); StyleButton(cancel,Color.FromArgb(190,18,60)); cancel.Click += delegate { CancelSelectedOrder(); }; orders.Controls.Add(cancel);
            lblStatus.Location=new Point(20,635); lblStatus.Size=new Size(1120,28); lblStatus.BackColor=Color.White; lblStatus.ForeColor=coffee; lblStatus.Font=new Font("Segoe UI",8,FontStyle.Bold); lblStatus.TextAlign=ContentAlignment.MiddleLeft; lblStatus.Padding=new Padding(10,0,0,0); Controls.Add(lblStatus);
            RefreshCartGrid(); UpdateCartTotal();
        }
        private Panel Card(int x,int y,int w,int h,string title)
        {
            Panel p=new Panel();
            p.Location=new Point(x,y);
            p.Size=new Size(w,h);
            p.BackColor=Color.White;
            p.BorderStyle=BorderStyle.FixedSingle;

            Label strip=new Label();
            strip.BackColor=Color.FromArgb(139,110,232);
            strip.Location=new Point(0,0);
            strip.Size=new Size(w,6);
            p.Controls.Add(strip);

            Label t=new Label();
            t.Text=title;
            t.ForeColor=Color.FromArgb(33,27,50);
            t.Font=new Font("Segoe UI",12,FontStyle.Bold);
            t.Location=new Point(18,18);
            t.Size=new Size(w-40,28);
            p.Controls.Add(t);

            return p;
        }

        private void AddLabel(Control p,string text,int x,int y)
        {
            Label l=new Label();
            l.Text=text;
            l.Location=new Point(x,y);
            l.Size=new Size(78,23);
            l.Font=new Font("Segoe UI",8,FontStyle.Bold);
            l.ForeColor=Color.FromArgb(92,84,112);
            p.Controls.Add(l);
        }

        private void StyleButton(Button b,Color c)
        {
            b.UseVisualStyleBackColor=false;
            b.BackColor=c;
            b.ForeColor=Color.White;
            b.FlatStyle=FlatStyle.Flat;
            b.FlatAppearance.BorderSize=0;
            b.Font=new Font("Segoe UI",8,FontStyle.Bold);
            b.Cursor=Cursors.Hand;
            b.TextAlign=ContentAlignment.MiddleCenter;
        }

        private void ConfigureGrid(DataGridView g)
        {
            g.AllowUserToAddRows=false;
            g.AllowUserToDeleteRows=false;
            g.ReadOnly=true;
            g.RowHeadersVisible=false;
            g.SelectionMode=DataGridViewSelectionMode.FullRowSelect;
            g.MultiSelect=false;
            g.AutoSizeColumnsMode=DataGridViewAutoSizeColumnsMode.Fill;
            g.BackgroundColor=Color.White;
            g.BorderStyle=BorderStyle.FixedSingle;
            g.EnableHeadersVisualStyles=false;
            g.ColumnHeadersDefaultCellStyle.BackColor=Color.FromArgb(240,234,255);
            g.ColumnHeadersDefaultCellStyle.ForeColor=Color.FromArgb(33,27,50);
            g.ColumnHeadersDefaultCellStyle.Font=new Font("Segoe UI",8,FontStyle.Bold);
            g.DefaultCellStyle.BackColor=Color.White;
            g.AlternatingRowsDefaultCellStyle.BackColor=Color.FromArgb(250,248,255);
            g.DefaultCellStyle.ForeColor=Color.FromArgb(33,27,50);
            g.DefaultCellStyle.SelectionBackColor=Color.FromArgb(139,110,232);
            g.DefaultCellStyle.SelectionForeColor=Color.White;
            g.DefaultCellStyle.Font=new Font("Segoe UI",8);
            g.RowTemplate.Height=25;
        }
        private Dictionary<string,object> GetApi(string q){ using(WebClient c=new WebClient()){ string r=c.DownloadString(apiUrl+"?"+q); return json.Deserialize<Dictionary<string,object>>(r); } }
        private string PostApi(Dictionary<string,string> data){ using(WebClient c=new WebClient()){ c.Headers[HttpRequestHeader.ContentType]="application/x-www-form-urlencoded"; List<string> parts=new List<string>(); foreach(KeyValuePair<string,string> i in data) parts.Add(Uri.EscapeDataString(i.Key)+"="+Uri.EscapeDataString(i.Value)); try{return c.UploadString(apiUrl,"POST",string.Join("&",parts.ToArray()));}catch(WebException ex){throw new Exception(ReadError(ex));}}}
        private string ReadError(WebException ex){ try{ if(ex.Response!=null){ using(Stream s=ex.Response.GetResponseStream()) using(StreamReader r=new StreamReader(s)){ string body=r.ReadToEnd(); Dictionary<string,object> d=json.Deserialize<Dictionary<string,object>>(body); if(d!=null && d.ContainsKey("message")) return Convert.ToString(d["message"]); return body; } } }catch{} return ex.Message; }
        private void RefreshAll(){ try{ LoadProducts(); LoadOrders(); lblStatus.Text="Connected to Paws & Care API."; }catch(Exception ex){ MessageBox.Show("Connection failed.\n\nRun PHP first inside php-api:\nphp -S localhost:8000\n\n"+ex.Message); } }
        private void LoadProducts(){ Dictionary<string,object> result=GetApi("action=list_active_products"); ArrayList rows=result["products"] as ArrayList; gridProducts.Columns.Clear(); gridProducts.Rows.Clear(); gridProducts.Columns.Add("id","ID"); gridProducts.Columns.Add("sku","SKU"); gridProducts.Columns.Add("name","Product"); gridProducts.Columns.Add("pet","Pet"); gridProducts.Columns.Add("price","Price"); gridProducts.Columns.Add("stock","Stock"); cboProduct.Items.Clear(); if(rows!=null){ foreach(object obj in rows){ Dictionary<string,object> p=obj as Dictionary<string,object>; if(p==null)continue; gridProducts.Rows.Add(p["id"],p["sku"],p["product_name"],p["pet_type"],p["price"],p["stock_qty"]); cboProduct.Items.Add(new ComboItem(Convert.ToString(p["product_name"])+" / P"+Convert.ToString(p["price"])+" / Stock "+Convert.ToString(p["stock_qty"]),Convert.ToString(p["id"]),p)); }} if(cboProduct.Items.Count>0)cboProduct.SelectedIndex=0; }
        private void LoadOrders(){ Dictionary<string,object> result=GetApi("action=list_orders&customer_id="+customerId); ArrayList rows=result["orders"] as ArrayList; gridOrders.Columns.Clear(); gridOrders.Rows.Clear(); gridOrders.Columns.Add("id","ID"); gridOrders.Columns.Add("order","Order No"); gridOrders.Columns.Add("date","Date"); gridOrders.Columns.Add("total","Total"); gridOrders.Columns.Add("payment","Payment"); gridOrders.Columns.Add("status","Status"); if(rows!=null){ foreach(object obj in rows){ Dictionary<string,object> o=obj as Dictionary<string,object>; if(o==null)continue; gridOrders.Rows.Add(o["id"],o["order_no"],o["order_date"],o["total_amount"],Convert.ToString(o["payment_method"])+" / "+Convert.ToString(o["payment_status"]),o["order_status"]); } } }
        private void AddToCart(){ if(cboProduct.SelectedItem==null){MessageBox.Show("Select a product first.");return;} ComboItem selected=cboProduct.SelectedItem as ComboItem; Dictionary<string,object> p=selected.Data; int pid=Convert.ToInt32(p["id"]); string name=Convert.ToString(p["product_name"]); decimal price=Convert.ToDecimal(p["price"]); int qty=(int)numQty.Value; int stock=Convert.ToInt32(p["stock_qty"]); int existing=0; foreach(CartLine l in cart) if(l.ProductId==pid) existing+=l.Quantity; if(existing+qty>stock){MessageBox.Show("Quantity exceeds available stock.");return;} bool updated=false; foreach(CartLine l in cart){ if(l.ProductId==pid){ l.Quantity+=qty; l.LineTotal=l.Quantity*l.UnitPrice; updated=true; break; }} if(!updated) cart.Add(new CartLine{ProductId=pid,ProductName=name,Quantity=qty,UnitPrice=price,LineTotal=price*qty}); RefreshCartGrid(); UpdateCartTotal(); }
        private void RemoveSelectedCartItem(){ if(gridCart.SelectedRows.Count==0){MessageBox.Show("Select an item from the cart.");return;} int index=gridCart.SelectedRows[0].Index; if(index>=0 && index<cart.Count) cart.RemoveAt(index); RefreshCartGrid(); UpdateCartTotal(); }
        private void RefreshCartGrid(){ gridCart.Columns.Clear(); gridCart.Rows.Clear(); gridCart.Columns.Add("product","Product"); gridCart.Columns.Add("qty","Qty"); gridCart.Columns.Add("price","Price"); gridCart.Columns.Add("total","Total"); foreach(CartLine l in cart) gridCart.Rows.Add(l.ProductName,l.Quantity,l.UnitPrice,l.LineTotal); }
        private void UpdateCartTotal(){ decimal subtotal=0; foreach(CartLine l in cart) subtotal+=l.LineTotal; decimal total=subtotal+numDeliveryFee.Value; lblCartTotal.Text="Subtotal: P"+subtotal.ToString("N2")+"   Delivery: P"+numDeliveryFee.Value.ToString("N2")+"   Total: P"+total.ToString("N2"); }
        private void Checkout(){ if(cart.Count==0){MessageBox.Show("Your cart is empty.");return;} if(txtAddress.Text.Trim()==""){MessageBox.Show("Delivery address is required.");return;} try{ ArrayList payload=new ArrayList(); foreach(CartLine l in cart){ Dictionary<string,object> row=new Dictionary<string,object>(); row["product_id"]=l.ProductId; row["quantity"]=l.Quantity; payload.Add(row);} string response=PostApi(new Dictionary<string,string>{{"action","create_order"},{"customer_id",customerId.ToString()},{"delivery_fee",numDeliveryFee.Value.ToString()},{"payment_method",cboPaymentMethod.Text},{"delivery_address",txtAddress.Text.Trim()},{"notes",txtNotes.Text.Trim()},{"items",json.Serialize(payload)}}); Dictionary<string,object> result=json.Deserialize<Dictionary<string,object>>(response); MessageBox.Show(Convert.ToString(result["message"])+"\nOrder: "+Convert.ToString(result["order_no"])); ClearCart(); RefreshAll(); }catch(Exception ex){MessageBox.Show("Checkout failed.\n\n"+ex.Message);} }
        private void ClearCart(){ cart.Clear(); RefreshCartGrid(); UpdateCartTotal(); }
        private void LoadSelectedOrderItems(){ if(gridOrders.SelectedRows.Count==0)return; string id=Convert.ToString(gridOrders.SelectedRows[0].Cells[0].Value); try{ Dictionary<string,object> result=GetApi("action=get_order&id="+Uri.EscapeDataString(id)); ArrayList rows=result["items"] as ArrayList; gridOrderItems.Columns.Clear(); gridOrderItems.Rows.Clear(); gridOrderItems.Columns.Add("product","Product"); gridOrderItems.Columns.Add("qty","Qty"); gridOrderItems.Columns.Add("price","Price"); gridOrderItems.Columns.Add("total","Total"); if(rows!=null){ foreach(object obj in rows){ Dictionary<string,object> item=obj as Dictionary<string,object>; if(item==null)continue; gridOrderItems.Rows.Add(item["product_name"],item["quantity"],item["unit_price"],item["line_total"]); } } }catch{} }
        private void CancelSelectedOrder(){ if(gridOrders.SelectedRows.Count==0){MessageBox.Show("Select an order first.");return;} string id=Convert.ToString(gridOrders.SelectedRows[0].Cells[0].Value); if(MessageBox.Show("Cancel selected order?","Cancel Order",MessageBoxButtons.YesNo)!=DialogResult.Yes)return; try{ string response=PostApi(new Dictionary<string,string>{{"action","cancel_order"},{"id",id},{"customer_id",customerId.ToString()}}); Dictionary<string,object> result=json.Deserialize<Dictionary<string,object>>(response); MessageBox.Show(Convert.ToString(result["message"])); RefreshAll(); }catch(Exception ex){ MessageBox.Show("Cancel failed.\n\n"+ex.Message); } }
    }
}
